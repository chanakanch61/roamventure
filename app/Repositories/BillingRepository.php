<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use DateTime;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BillingRepository
{
    use ResolvesServices;

    // False once a statement in the open transaction failed. Shared with the repositories that call event() and issue() inside theirs.
    public $trans_ok = true;

    public function begin()
    {
        DB::beginTransaction();
        $this->trans_ok = true;
    }

    // For a statement whose result legacy never read: a failure returned FALSE, marked the transaction failed and the code carried on to its
    // end-of-transaction check. Reads legacy chained on (->row_array(), count_all_results) were fatal when they failed, so they stay outside this.
    public function soft($statement)
    {
        try {
            return $statement();
        } catch (QueryException $e) {
            $this->trans_ok = false;

            return false;
        }
    }

    public function cents($v)
    {
        return (int) round((float) $v * 100);
    }

    public function decimal($v)
    {
        return number_format($v / 100, 2, '.', '');
    }

    public function by_token($token)
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) ? DB::table('bookings')->where('access_token', $token)->first() : null;
    }

    public function event($id, $message, $actor = null)
    {
        $this->soft(fn () => DB::table('booking_events')->insert(['booking_id' => $id, 'message' => $message, 'actor_id' => $actor, 'created_at' => date('Y-m-d H:i:s')]));
    }

    public function invoice($id)
    {
        return DB::table('invoices')->where('booking_id', $id)->first();
    }

    public function paid($id)
    {
        $r = DB::table('payments')->selectRaw('SUM(reported_amount) AS paid')->where('booking_id', $id)->where('status', 'approved')->first();

        return $this->cents($r['paid'] ?? 0);
    }

    public function account()
    {
        $s = $this->trips->settings();

        return array_intersect_key($s, array_flip(['company_name', 'company_address', 'company_tax_id', 'phone', 'email', 'bank_name', 'bank_account', 'bank_holder', 'promptpay_number', 'payment_qr']));
    }

    public function account_ready($a)
    {
        return ! empty($a['bank_holder']) && ((! empty($a['bank_name']) && ! empty($a['bank_account'])) || ! empty($a['promptpay_number']) || ! empty($a['payment_qr']));
    }

    // Called within the existing booking transaction, after tour/departure/booking locks.
    public function issue($b, $hours = 24)
    {
        if ($this->invoice($b['id'])) {
            return;
        }
        $tour = DB::table('tours')->where('id', $b['tour_id'])->first();
        $dep = DB::table('departures')->where('id', $b['departure_id'])->first();
        $package = json_decode($b['package_snapshot'] ?? 'null', true);
        if ($package) {
            $dep = $package;
            $tour['deposit_per_person'] = $package['deposit_per_person'] ?? null;
        }
        $now = date('Y-m-d H:i:s');
        $departureEnd = $b['start_date'].' 23:59:59';
        $due = min(date('Y-m-d H:i:s', time() + $hours * 3600), $departureEnd);
        $balance = max($due, date('Y-m-d 23:59:59', strtotime($b['start_date'].' -'.(int) ($dep['balance_due_days'] ?? 14).' days')));
        $token = $b['access_token'] ?: bin2hex(random_bytes(32));
        $this->soft(fn () => DB::table('bookings')->where('id', $b['id'])->update(['access_token' => $token]));
        $deposit = min($this->cents($b['total_price']), $this->cents($tour['deposit_per_person'] ?? 0) * (int) $b['traveler_count']);
        $actor = session('admin_id') ?: null;
        $invoice = ['booking_id' => $b['id'], 'reference' => 'INV-'.date('Ymd').'-'.str_pad($b['id'], 6, '0', STR_PAD_LEFT), 'snapshot' => json_encode(['booking' => $b, 'seller' => $this->account()], JSON_UNESCAPED_UNICODE), 'total_amount' => $b['total_price'], 'deposit_amount' => $this->decimal($deposit), 'deposit_enabled' => ! empty($dep['deposit_enabled']) && $deposit > 0 ? 1 : 0, 'deposit_cutoff_days' => $dep['deposit_cutoff_days'] ?? 30, 'payment_due_at' => $due, 'balance_due_at' => $balance, 'issued_at' => $now, 'issued_by' => $actor];
        $this->soft(fn () => DB::table('invoices')->insert($invoice));
        $this->event($b['id'], 'ออกใบแจ้งหนี้และเปิดรับการชำระเงิน', $actor);
    }

    public function state($b)
    {
        $invoice = $this->invoice($b['id']);
        $paid = $this->paid($b['id']);
        $remaining = max(0, $this->cents($invoice['total_amount'] ?? $b['total_price']) - $paid);
        $days = (int) (new DateTime('today'))->diff(new DateTime($b['start_date']))->format('%r%a');
        $deposit = $invoice && $invoice['deposit_enabled'] && $days >= (int) $invoice['deposit_cutoff_days'] && $paid === 0 && $this->cents($invoice['deposit_amount']) > 0 && $this->cents($invoice['deposit_amount']) < $remaining && $invoice['balance_due_at'] > date('Y-m-d H:i:s');
        $payments = DB::table('payments')->where('booking_id', $b['id'])->orderBy('id', 'DESC')->get()->all();
        $active = null;
        foreach ($payments as $p) {
            if ($p['status'] === 'pending' || ($p['status'] === 'awaiting_upload' && $p['expires_at'] >= date('Y-m-d H:i:s'))) {
                $active = $p;
                break;
            }
        }

        return ['invoice' => $invoice, 'paid' => $paid, 'remaining' => $remaining, 'deposit_allowed' => $deposit, 'payments' => $payments, 'active' => $active, 'due' => $invoice ? ($paid > 0 ? $invoice['balance_due_at'] : $invoice['payment_due_at']) : null, 'events' => DB::table('booking_events')->where('booking_id', $b['id'])->orderBy('id', 'DESC')->get()->all()];
    }

    private function lock($id)
    {
        $this->begin();

        return DB::select('SELECT * FROM bookings WHERE id=? FOR UPDATE', [$id])[0] ?? null;
    }

    private function fail($message)
    {
        DB::rollBack();

        return $message;
    }

    private function finish()
    {
        if (! $this->trans_ok) {
            return $this->fail('บันทึกไม่สำเร็จ กรุณาลองใหม่');
        } DB::commit();

        return null;
    }

    public function start($id, $kind)
    {
        $b = $this->lock($id);
        if (! $b) {
            return $this->fail('ไม่พบการจอง');
        } $s = $this->state($b);
        if ($b['status'] !== 'confirmed' || ! $s['invoice'] || ! $s['remaining'] || $b['start_date'] < date('Y-m-d')) {
            return $this->fail('การจองนี้ยังไม่เปิดชำระเงินหรือไม่มียอดคงเหลือ');
        }
        if ($s['active']) {
            return $this->fail('มีรายการชำระที่กำลังดำเนินการอยู่');
        }
        if (! in_array($kind, ['deposit', 'full'], true) || ($kind === 'deposit' && ! $s['deposit_allowed'])) {
            return $this->fail('รอบนี้ไม่เข้าเงื่อนไขมัดจำ กรุณาชำระยอดคงเหลือเต็มจำนวน');
        }
        if ($s['due'] < date('Y-m-d H:i:s')) {
            return $this->fail('พ้นกำหนดชำระ กรุณาติดต่อทีมงานเพื่อตรวจสอบและขยายเวลา');
        }
        $account = $this->account();
        if (! $this->account_ready($account)) {
            return $this->fail('ทีมงานยังไม่ได้ตั้งค่าบัญชีรับเงิน กรุณาติดต่อทีมงาน');
        }
        $this->soft(fn () => DB::table('payments')->where('booking_id', $id)->where('status', 'awaiting_upload')->update(['status' => 'expired']));
        $expires = min($s['due'], date('Y-m-d H:i:s', time() + 86400));
        if ($kind === 'deposit') {
            $expires = min($expires, date('Y-m-d 23:59:59', strtotime($b['start_date'].' -'.(int) $s['invoice']['deposit_cutoff_days'].' days')));
        }
        $this->soft(fn () => DB::table('payments')->insert(['booking_id' => $id, 'reference' => 'PAY-'.strtoupper(bin2hex(random_bytes(8))), 'kind' => $kind, 'expected_amount' => $kind === 'deposit' ? $s['invoice']['deposit_amount'] : $this->decimal($s['remaining']), 'status' => 'awaiting_upload', 'account_snapshot' => json_encode($account, JSON_UNESCAPED_UNICODE), 'expires_at' => $expires, 'created_at' => date('Y-m-d H:i:s')]));
        $this->event($id, $kind === 'deposit' ? 'เริ่มรายการชำระมัดจำ' : 'เริ่มรายการชำระยอดคงเหลือ');

        return $this->finish();
    }

    public function cancel_attempt($id, $paymentId)
    {
        $b = $this->lock($id);
        $p = DB::table('payments')->where('booking_id', $id)->where('id', $paymentId)->first();
        if (! $b || ! $p || $p['status'] !== 'awaiting_upload') {
            return $this->fail('ยกเลิกได้เฉพาะรายการที่ยังไม่ได้ส่งสลิป');
        }
        $this->soft(fn () => DB::table('payments')->where('id', $p['id'])->update(['status' => 'cancelled']));
        $this->event($id, 'ยกเลิกรายการชำระที่ยังไม่ได้ส่งสลิป');

        return $this->finish();
    }

    public function submit_slip($id, $paymentId, $fields, $file)
    {
        $b = $this->lock($id);
        $p = DB::table('payments')->where('booking_id', $id)->where('id', $paymentId)->first();
        if (! $b || $b['status'] !== 'confirmed' || ! $p || $p['status'] !== 'awaiting_upload') {
            return $this->fail('รายการนี้ไม่เปิดรับสลิปหรือส่งสลิปแล้ว');
        }
        // Allow proof of an on-time transfer to be uploaded after expiry for manual review.
        $time = DateTime::createFromFormat('!Y-m-d\TH:i', $fields['transferred_at']);
        if (! $time || $time->format('Y-m-d\TH:i') !== $fields['transferred_at'] || $time->getTimestamp() > time() + 300 || $time->format('Y-m-d H:i:59') < $p['created_at'] || $time->format('Y-m-d H:i:s') > $p['expires_at']) {
            return $this->fail('วันเวลาโอนต้องอยู่ในช่วงรายการชำระและไม่เกินเวลาปัจจุบัน');
        }
        if (! preg_match('/^\d{1,12}(\.\d{1,2})?$/D', $fields['reported_amount']) || $this->cents($fields['reported_amount']) <= 0 || $this->cents($fields['reported_amount']) > $this->state($b)['remaining']) {
            return $this->fail('ยอดโอนต้องมากกว่า 0 และไม่เกินยอดคงเหลือ หากโอนเกินกรุณาติดต่อทีมงาน');
        }
        $a = json_decode($p['account_snapshot'], true);
        if (! in_array($fields['channel'], ['bank', 'promptpay'], true) || ($fields['channel'] === 'bank' && (empty($a['bank_name']) || empty($a['bank_account']))) || ($fields['channel'] === 'promptpay' && empty($a['promptpay_number']) && empty($a['payment_qr']))) {
            return $this->fail('กรุณาเลือกช่องทางโอนที่เปิดให้บริการ');
        }
        if (DB::table('payments')->where('slip_hash', $file['hash'])->whereIn('status', ['pending', 'approved'])->count()) {
            return $this->fail('ไฟล์สลิปนี้อยู่ระหว่างตรวจสอบหรือใช้ยืนยันแล้ว กรุณาติดต่อทีมงาน');
        }
        // A failed statement is not 1 affected row either: legacy read -1 from affected_rows() and took the same branch.
        $claimed = $this->soft(fn () => DB::affectingStatement('INSERT IGNORE INTO payment_slip_claims (slip_hash,payment_id) VALUES (?,?)', [$file['hash'], $p['id']]));
        if ($claimed !== 1) {
            return $this->fail('สลิปนี้มีรายการอื่นรับไว้แล้ว กรุณาติดต่อทีมงาน');
        }
        $this->soft(fn () => DB::table('payments')->where('id', $p['id'])->update(['status' => 'pending', 'reported_amount' => $fields['reported_amount'], 'transferred_at' => $time->format('Y-m-d H:i:s'), 'channel' => $fields['channel'], 'submitted_at' => date('Y-m-d H:i:s'), 'slip_path' => $file['path'], 'slip_hash' => $file['hash']]));
        $this->event($id, 'ได้รับสลิปแล้ว รอทีมงานตรวจสอบ');

        return $this->finish();
    }

    public function review($id, $paymentId, $decision, $note, $ack = false)
    {
        $b = $this->lock($id);
        $p = DB::table('payments')->where('booking_id', $id)->where('id', $paymentId)->first();
        if (! $b || ! $p || $p['status'] !== 'pending') {
            return $this->fail('รายการนี้ตรวจสอบแล้วหรือยังไม่มีสลิป');
        }
        if (! in_array($decision, ['approve', 'reject'], true) || mb_strlen($note) > 1000 || ($decision === 'reject' && trim($note) === '')) {
            return $this->fail('กรุณาระบุผลตรวจสอบและเหตุผลเมื่อไม่ผ่าน');
        }
        $actor = session('admin_id') ?: null;
        $now = date('Y-m-d H:i:s');
        $data = ['status' => $decision === 'approve' ? 'approved' : 'rejected', 'review_note' => $note, 'reviewed_by' => $actor, 'reviewed_at' => $now];
        if ($decision === 'approve') {
            if ($b['status'] !== 'confirmed') {
                return $this->fail('ต้องยืนยันการจองก่อนอนุมัติสลิป');
            }
            $s = $this->state($b);
            if (! $s['invoice'] || $this->cents($p['reported_amount']) > $s['remaining']) {
                return $this->fail('ยอดรับเกินยอดคงเหลือ กรุณาตรวจสอบ');
            }
            if ($this->cents($p['reported_amount']) !== $this->cents($p['expected_amount']) && ! $ack) {
                return $this->fail('ยอดโอนไม่ตรงยอดเรียกเก็บ กรุณายืนยันว่าตรวจสอบยอดจริงแล้ว');
            }
            $data['receipt_reference'] = 'RCT-'.date('Ymd').'-'.str_pad($p['id'], 6, '0', STR_PAD_LEFT);
            $data['receipt_snapshot'] = json_encode(['invoice' => json_decode($s['invoice']['snapshot'], true), 'invoice_reference' => $s['invoice']['reference'], 'payment_reference' => $p['reference'], 'amount' => $p['reported_amount'], 'channel' => $p['channel'], 'transferred_at' => $p['transferred_at'], 'issued_at' => $now], JSON_UNESCAPED_UNICODE);
        }
        $this->soft(fn () => DB::table('payments')->where('id', $p['id'])->update($data));
        if ($decision === 'reject') {
            $this->soft(fn () => DB::table('payment_slip_claims')->where('payment_id', $p['id'])->delete());
        }
        $this->event($id, $decision === 'approve' ? 'ยืนยันรับเงิน ฿'.money($p['reported_amount']).' และออกใบเสร็จ '.$data['receipt_reference'] : 'สลิปไม่ผ่าน: '.$note, $actor);

        return $this->finish();
    }

    public function extend($id, $hours)
    {
        $b = $this->lock($id);
        $i = $this->invoice($id);
        if (! $b || $b['status'] !== 'confirmed' || ! $i || $hours < 1 || $hours > 720 || $b['start_date'] < date('Y-m-d')) {
            return $this->fail('ขยายเวลาได้ 1–720 ชั่วโมง สำหรับการจองที่ยืนยันและยังไม่เดินทาง');
        }
        $due = min(date('Y-m-d H:i:s', time() + $hours * 3600), $b['start_date'].' 23:59:59');
        $data = $this->paid($id) > 0 ? ['balance_due_at' => $due] : ['payment_due_at' => $due, 'balance_due_at' => max($i['balance_due_at'],$due)];
        $this->soft(fn () => DB::table('invoices')->where('id',$i['id'])->update($data));
        $this->event($id,'ปรับกำหนดชำระเป็น '.$due,session('admin_id') ?: null);

        return $this->finish();
    }
}
