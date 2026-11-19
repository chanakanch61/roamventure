<?php

namespace App\Http\Controllers;

use App\Repositories\GroupInvoiceRepository as Invoices;
use App\Repositories\GroupQuoteRepository as Quotes;
use Illuminate\Support\Facades\DB;

/**
 * Invoices, payments, and receipts / tax invoices.
 * installment: a share of a group quotation. charges: costs outside the tour price (visa fees, sundries)
 * with their own line items, made from a quotation, a booking, or on their own.
 */
class GroupInvoiceAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    private function value(string $key): string
    {
        $v = request()->post($key);

        return is_string($v) ? trim($v) : '';
    }

    public function index()
    {
        $filters = [];
        foreach (['q', 'status', 'kind', 'page'] as $k) {
            $v = request()->query($k);
            $filters[$k] = is_string($v) ? mb_substr(trim($v), 0, 180) : '';
        }

        return $this->render('admin/group_invoices', $this->group_invoices->listing($filters) + ['title' => 'ใบแจ้งหนี้', 'filters' => $filters], true);
    }

    /**
     * New invoice. ?quote=ID bills the next installment of a quotation (prefilled with a sensible share);
     * ?kind=charges makes an invoice of other costs, prefilled from ?quote=ID or ?booking=ID when given.
     */
    public function create()
    {
        $quote = $this->group_quotes->find((int) request()->query('quote'));
        $booking = $quote ? null : $this->booking((int) request()->query('booking'));
        if (request()->query('kind') === 'charges' || ! $quote) {
            return $this->create_charges($quote, $booking);
        }
        $existing = array_filter($this->group_invoices->for_quote((int) $quote['id']), fn ($i) => $i['kind'] === 'installment' && $i['status'] !== 'cancelled');
        $invoice = $this->customer_from($quote) + [
            'id' => 0, 'number' => '', 'kind' => 'installment', 'status' => 'unpaid',
            'label' => $existing ? 'งวดที่ '.(count($existing) + 1).' ส่วนที่เหลือ' : 'ชำระเต็มจำนวน',
            'share_type' => 'remaining', 'share_value' => '',
            'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+7 days')), 'notes' => '',
        ];
        $errors = [];
        if (request_method() === 'post') {
            [$invoice, $amounts, $errors] = $this->read($invoice, $quote, 0);
            if (! $errors) {
                $this->store($this->fields($invoice) + $amounts + [
                    'kind' => 'installment', 'quotation_id' => $quote['id'], 'request_id' => $quote['request_id'],
                    'quote_number' => $quote['number'], 'quote_total' => $quote['total'], 'title' => $quote['title'],
                    'travel_dates' => $quote['travel_dates'], 'travelers' => $quote['travelers'],
                    'vat_mode' => $quote['vat_mode'], 'vat_rate' => $quote['vat_rate'], 'wht_enabled' => $quote['wht_enabled'] ?? 0, 'wht_rate' => $quote['wht_rate'] ?? 0,
                ]);
                $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
        }

        return $this->form($invoice, $quote, $errors);
    }

    private function create_charges(?array $quote, ?array $booking)
    {
        $invoice = [
            'id' => 0, 'number' => '', 'kind' => 'charges', 'status' => 'unpaid', 'label' => 'ค่าใช้จ่ายเพิ่มเติม',
            'share_type' => 'items', 'share_value' => 0,
            'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+7 days')),
            'quotation_id' => null, 'request_id' => null, 'quote_number' => '', 'booking_id' => null, 'booking_reference' => '',
            'customer_name' => '', 'customer_company' => '', 'customer_address' => '', 'customer_tax_id' => '', 'customer_branch' => '',
            'customer_phone' => '', 'customer_email' => '', 'seller_branch' => 'สำนักงานใหญ่', 'notes' => '',
            'title' => '', 'travel_dates' => '', 'travelers' => null,
            'vat_mode' => 'none', 'vat_rate' => 7, 'wht_enabled' => 0, 'wht_rate' => 3,
            'items' => [['description' => '', 'qty' => 1, 'unit' => 'ท่าน', 'price' => 0, 'disbursement' => 0]],
        ];
        if ($quote) {
            $invoice = array_merge($invoice, $this->customer_from($quote), [
                'quotation_id' => (int) $quote['id'], 'request_id' => $quote['request_id'], 'quote_number' => $quote['number'],
                'title' => $quote['title'], 'travel_dates' => $quote['travel_dates'], 'travelers' => $quote['travelers'],
                'vat_mode' => $quote['vat_mode'], 'vat_rate' => $quote['vat_rate'], 'wht_enabled' => $quote['wht_enabled'] ?? 0, 'wht_rate' => $quote['wht_rate'] ?? 3,
            ]);
        } elseif ($booking) {
            $invoice = array_merge($invoice, [
                'booking_id' => (int) $booking['id'], 'booking_reference' => $booking['reference'],
                'customer_name' => $booking['customer_name'], 'customer_phone' => $booking['phone'], 'customer_email' => $booking['email'],
                'title' => $booking['tour_title'], 'travel_dates' => thai_date($booking['start_date']).' – '.thai_date($booking['end_date']),
                'travelers' => (int) ($booking['traveler_count'] ?: $booking['group_size']) ?: null,
            ]);
        }
        $invoice['items'][0]['qty'] = $invoice['travelers'] ?: 1;
        $errors = [];
        if (request_method() === 'post') {
            [$invoice, $amounts, $errors] = $this->read_charges($invoice);
            if (! $errors) {
                $this->store($this->fields($invoice) + $amounts + array_intersect_key($invoice, array_flip(['quotation_id', 'request_id', 'quote_number', 'booking_id', 'booking_reference'])) + ['kind' => 'charges', 'share_type' => 'items', 'share_value' => 0, 'quote_total' => 0]);
                $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
        }

        return $this->form($invoice, $quote, $errors);
    }

    /** Inserts a new unpaid invoice and opens it; returns only when the insert failed. */
    private function store(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $id = $this->group_invoices->insert($data + ['status' => 'unpaid', 'created_by' => (string) session('admin_name'), 'created_at' => $now, 'updated_at' => $now], code_prefix($this->settings));
        if ($id) {
            session()->flash('success', 'สร้างใบแจ้งหนี้เรียบร้อยแล้ว');
            redirect_to(base_url().'admin/group-invoices/'.$id);
        }
    }

    public function edit($id)
    {
        $invoice = $this->group_invoices->find($id);
        if (! $invoice) {
            show_404();
        }
        $quote = $invoice['quotation_id'] ? $this->group_quotes->find($invoice['quotation_id']) : null;
        $charges = $invoice['kind'] === 'charges';
        $errors = [];
        if (request_method() === 'post') {
            if ($invoice['status'] !== 'unpaid' || (! $charges && ! $quote)) {
                show_error('แก้ไขได้เฉพาะใบแจ้งหนี้ที่ยังรอชำระ', 409);
            }
            [$invoice, $amounts, $errors] = $charges ? $this->read_charges($invoice) : $this->read($invoice, $quote, (int) $invoice['id']);
            if (! $errors) {
                $this->group_invoices->update((int) $invoice['id'], $this->fields($invoice) + $amounts);
                session()->flash('success', 'บันทึกใบแจ้งหนี้เรียบร้อยแล้ว');
                redirect_to(base_url().'admin/group-invoices/'.(int) $invoice['id']);
            }
        }

        return $this->form($invoice, $quote, $errors);
    }

    public function pay($id)
    {
        $this->post_only();
        $invoice = $this->group_invoices->find($id);
        if (! $invoice || $invoice['status'] !== 'unpaid') {
            show_error('ใบแจ้งหนี้นี้ไม่ได้อยู่ในสถานะรอชำระ', 409);
        }
        $paidAt = $this->value('paid_at');
        $amount = str_replace([',', ' '], '', $this->value('paid_amount'));
        $method = $this->value('payment_method');
        $note = $this->value('payment_note');
        $error = '';
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $paidAt) || ! strtotime($paidAt) || $paidAt > date('Y-m-d')) {
            $error = 'วันที่รับชำระไม่ถูกต้อง (ต้องไม่เป็นวันในอนาคต)';
        } elseif (! is_numeric($amount) || (float) $amount <= 0 || (float) $amount > (float) $invoice['total']) {
            $error = 'ยอดรับชำระต้องมากกว่า 0 และไม่เกินยอดใบแจ้งหนี้';
        } elseif (! isset(Invoices::payment_methods()[$method]) || mb_strlen($note) > 255) {
            $error = 'ช่องทางชำระหรือหมายเหตุไม่ถูกต้อง';
        }
        if ($error) {
            session()->flash('error', $error);
            redirect_to(base_url().'admin/group-invoices/'.(int) $invoice['id'].'#payment');
        }
        $receipt = $this->group_invoices->mark_paid((int) $invoice['id'], code_prefix($this->settings), ['paid_at' => $paidAt, 'paid_amount' => round((float) $amount, 2), 'payment_method' => $method, 'payment_note' => $note]);
        if (! $receipt) {
            show_error('บันทึกการรับชำระไม่สำเร็จ กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง', 409);
        }
        // A paid installment means the customer has taken the offer. Extra charges say nothing about that.
        if ($invoice['kind'] === 'installment') {
            $quote = $this->group_quotes->find($invoice['quotation_id']);
            if ($quote && in_array($quote['status'], ['draft', 'sent'], true)) {
                $this->group_quotes->update((int) $quote['id'], ['status' => 'accepted', 'updated_at' => date('Y-m-d H:i:s')]);
            }
            $this->group_quotes->sync_request($invoice['request_id'] ? (int) $invoice['request_id'] : null, 'accepted');
        }
        session()->flash('success', 'บันทึกรับชำระแล้ว ออกใบเสร็จเลขที่ '.$receipt);
        redirect_to(base_url().'admin/group-invoices/'.(int) $invoice['id']);
    }

    public function cancel($id)
    {
        $this->post_only();
        $invoice = $this->group_invoices->find($id);
        if (! $invoice || $invoice['status'] === 'cancelled') {
            show_404();
        }
        $reason = $this->value('cancel_reason');
        if ($reason === '' || mb_strlen($reason) > 200) {
            session()->flash('error', 'กรุณาระบุเหตุผลที่ยกเลิก (ไม่เกิน 200 ตัวอักษร)');
            redirect_to(base_url().'admin/group-invoices/'.(int) $invoice['id']);
        }
        $note = trim(($invoice['notes'] ? $invoice['notes']."\n" : '').'ยกเลิกเมื่อ '.thai_date(date('Y-m-d')).' โดย '.session('admin_name').': '.$reason);
        $this->group_invoices->update((int) $invoice['id'], ['status' => 'cancelled', 'notes' => $note]);
        session()->flash('success', 'ยกเลิก '.$invoice['number'].($invoice['receipt_number'] ? ' และใบเสร็จ '.$invoice['receipt_number'] : '').' แล้ว เลขที่เดิมจะไม่ถูกนำกลับมาใช้');
        redirect_to(base_url().'admin/group-invoices/'.(int) $invoice['id']);
    }

    /** Saved line items for charges invoices: one form, the posted rows replace the list. */
    public function templates()
    {
        $rows = $this->group_invoices->templates();
        $errors = [];
        if (request_method() === 'post') {
            $rows = [];
            $posted = request()->post('items');
            foreach (is_array($posted) ? $posted : [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $text = fn ($k) => is_string($row[$k] ?? null) ? trim($row[$k]) : '';
                if ($text('description') === '') {
                    continue; // Blank row.
                }
                $price = str_replace([',', ' '], '', $text('price'));
                $item = ['description' => $text('description'), 'unit' => $text('unit'), 'price' => is_numeric($price) ? round((float) $price, 2) : 0, 'disbursement' => $text('disbursement') === '1' ? 1 : 0];
                if (mb_strlen($item['description']) > 300 || mb_strlen($item['unit']) > 30) {
                    $errors[] = 'ชื่อรายการต้องไม่เกิน 300 ตัวอักษร และหน่วยไม่เกิน 30 ตัวอักษร';
                }
                if ($price !== '' && (! is_numeric($price) || (float) $price < 0 || (float) $price > 99999999)) {
                    $errors[] = 'ราคาของ “'.mb_substr($item['description'], 0, 40).'” ไม่ถูกต้อง';
                }
                $rows[] = $item;
            }
            $names = array_map(fn ($r) => mb_strtolower($r['description']), $rows);
            if (count($names) !== count(array_unique($names))) {
                $errors[] = 'มีชื่อรายการซ้ำกัน';
            }
            if (count($rows) > 100) {
                $errors[] = 'template ต้องไม่เกิน 100 รายการ';
            }
            if (! $errors) {
                $this->group_invoices->save_templates($rows);
                session()->flash('success', 'บันทึก template รายการแล้ว ('.count($rows).' รายการ)');
                redirect_to(base_url().'admin/group-invoices/templates');
            }
        }

        return $this->render('admin/group_invoice_templates', ['title' => 'Template รายการค่าใช้จ่าย', 'rows' => $rows, 'errors' => array_values(array_unique($errors)), 'group_quote_page' => true], true);
    }

    public function print($id)
    {
        return $this->document($id, false);
    }

    public function receipt($id)
    {
        return $this->document($id, true);
    }

    private function document($id, bool $receipt)
    {
        $invoice = $this->group_invoices->find($id);
        if (! $invoice || ($receipt && ! $invoice['receipt_number'])) {
            show_404();
        }

        return response(load_view('admin/group_invoice_print', [
            'invoice' => $invoice,
            'receipt' => $receipt,
            'settings' => $this->settings,
            'account' => $this->billing->account(),
            'license' => tour_license($this->settings),
            'company' => trim($this->settings['company_name'] ?? '') ?: 'RoamVenture',
        ], true));
    }

    private function booking(int $id): ?array
    {
        return $id ? DB::table('bookings')->where('id', $id)->first() : null;
    }

    private function customer_from(array $quote): array
    {
        return [
            'customer_name' => $quote['customer_name'], 'customer_company' => $quote['customer_company'], 'customer_address' => $quote['customer_address'],
            'customer_tax_id' => $quote['customer_tax_id'], 'customer_branch' => $quote['customer_company'] !== '' ? 'สำนักงานใหญ่' : '',
            'customer_phone' => $quote['customer_phone'], 'customer_email' => $quote['customer_email'], 'seller_branch' => 'สำนักงานใหญ่',
        ];
    }

    private function form(array $invoice, ?array $quote, array $errors)
    {
        if ($invoice['kind'] === 'charges') {
            // Other invoices of the same quotation or booking, for context.
            $related = $invoice['quotation_id'] ? $this->group_invoices->for_quote((int) $invoice['quotation_id']) : ($invoice['booking_id'] ? $this->group_invoices->for_booking((int) $invoice['booking_id']) : []);
        } else {
            $related = $quote ? array_filter($this->group_invoices->for_quote((int) $quote['id']), fn ($i) => $i['kind'] === 'installment') : [];
        }

        return $this->render('admin/group_invoice_form', [
            'templates' => $invoice['kind'] === 'charges' ? $this->group_invoices->templates() : [],
            'title' => $invoice['id'] ? 'ใบแจ้งหนี้ '.$invoice['number'] : ($invoice['kind'] === 'charges' ? 'ใบแจ้งหนี้ค่าใช้จ่ายอื่น' : 'สร้างใบแจ้งหนี้'),
            'invoice' => $invoice,
            'quote' => $quote,
            'others' => array_values(array_filter($related, fn ($i) => (int) $i['id'] !== (int) $invoice['id'])),
            'billed' => $quote && $invoice['kind'] === 'installment' ? $this->group_invoices->billed((int) $quote['id'], (int) $invoice['id']) : ['total' => 0, 'subtotal' => 0],
            'errors' => $errors,
            'group_quote_page' => true,
        ], true);
    }

    private function fields(array $invoice): array
    {
        $keys = ['label', 'share_type', 'share_value', 'issue_date', 'due_date', 'customer_name', 'customer_company', 'customer_address', 'customer_tax_id', 'customer_branch', 'customer_phone', 'customer_email', 'seller_branch', 'notes'];
        if ($invoice['kind'] === 'charges') {
            $keys = array_merge($keys, ['title', 'travel_dates', 'travelers', 'vat_mode', 'vat_rate', 'wht_enabled', 'wht_rate']);
            $invoice['items'] = json_encode(array_values($invoice['items']), JSON_UNESCAPED_UNICODE);
            $keys[] = 'items';
        }

        return array_intersect_key($invoice, array_flip($keys));
    }

    /** Fields every invoice has: label, dates, customer, notes. */
    private function read_common(array $invoice, array &$errors): array
    {
        foreach (['label' => 200, 'customer_name' => 180, 'customer_company' => 200, 'customer_address' => 1000, 'customer_tax_id' => 30, 'customer_branch' => 60, 'customer_phone' => 40, 'customer_email' => 190, 'seller_branch' => 60, 'notes' => 3000] as $key => $max) {
            $invoice[$key] = $this->value($key);
            if (mb_strlen($invoice[$key]) > $max) {
                $errors[] = 'ข้อความยาวเกินกำหนด: '.$key;
            }
        }
        if ($invoice['label'] === '') {
            $errors[] = $invoice['kind'] === 'charges' ? 'กรุณาระบุหัวข้อใบแจ้งหนี้ เช่น ค่าวีซ่าและค่าใช้จ่ายเพิ่มเติม' : 'กรุณาระบุชื่องวด เช่น งวดที่ 1 มัดจำ 30%';
        }
        if ($invoice['customer_name'] === '') {
            $errors[] = 'กรุณากรอกชื่อลูกค้า';
        }
        if ($invoice['customer_email'] !== '' && ! filter_var($invoice['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'อีเมลลูกค้าไม่ถูกต้อง';
        }
        foreach (['issue_date' => 'วันที่ออก', 'due_date' => 'วันครบกำหนดชำระ'] as $key => $label) {
            $date = $this->value($key);
            $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) && strtotime($date);
            if (($key === 'issue_date' && ! $ok) || ($date !== '' && ! $ok)) {
                $errors[] = 'กรุณาระบุ'.$label.'ให้ถูกต้อง';
            }
            $invoice[$key] = $date !== '' ? $date : null;
        }
        if ($invoice['due_date'] && $invoice['issue_date'] && $invoice['due_date'] < $invoice['issue_date']) {
            $errors[] = 'วันครบกำหนดต้องไม่ก่อนวันที่ออก';
        }

        return $invoice;
    }

    /** Reads a posted installment; amounts are always worked out here from the quotation. */
    private function read(array $invoice, array $quote, int $exceptId): array
    {
        $errors = [];
        $invoice = $this->read_common($invoice, $errors);
        $invoice['share_type'] = $this->value('share_type');
        if (! isset(Invoices::share_types()[$invoice['share_type']])) {
            $errors[] = 'รูปแบบการเรียกเก็บไม่ถูกต้อง';
            $invoice['share_type'] = 'remaining';
        }
        $raw = str_replace([',', ' '], '', $this->value('share_value'));
        $invoice['share_value'] = $invoice['share_type'] === 'remaining' ? 0 : (is_numeric($raw) ? round((float) $raw, 2) : 0);
        if ($invoice['share_type'] !== 'remaining' && ! is_numeric($raw)) {
            $errors[] = $invoice['share_type'] === 'percent' ? 'กรุณาระบุเปอร์เซ็นต์' : 'กรุณาระบุจำนวนเงิน';
        }
        [$amounts, $error] = Invoices::share($quote, $invoice['share_type'], (float) $invoice['share_value'], $this->group_invoices->billed((int) $quote['id'], $exceptId));
        if ($error) {
            $errors[] = $error;
        }

        return [$invoice, $amounts, array_values(array_unique($errors))];
    }

    /** Reads a posted charges invoice: trip details, VAT / withholding, and line items. Totals come from the server. */
    private function read_charges(array $invoice): array
    {
        $errors = [];
        $invoice = $this->read_common($invoice, $errors);
        foreach (['title' => 250, 'travel_dates' => 150] as $key => $max) {
            $invoice[$key] = $this->value($key);
            if (mb_strlen($invoice[$key]) > $max) {
                $errors[] = 'ข้อความยาวเกินกำหนด: '.$key;
            }
        }
        $travelers = $this->value('travelers');
        $invoice['travelers'] = $travelers === '' ? null : filter_var($travelers, FILTER_VALIDATE_INT);
        if ($travelers !== '' && ($invoice['travelers'] === false || $invoice['travelers'] < 1 || $invoice['travelers'] > 9999)) {
            $errors[] = 'จำนวนผู้เดินทางต้องอยู่ระหว่าง 1–9999';
            $invoice['travelers'] = null;
        }
        $money = function (string $raw, string $label, float $max) use (&$errors): float {
            $raw = str_replace([',', ' '], '', $raw);
            if ($raw === '') {
                return 0.0;
            }
            if (! is_numeric($raw) || (float) $raw < 0 || (float) $raw > $max) {
                $errors[] = $label.'ไม่ถูกต้อง';

                return 0.0;
            }

            return round((float) $raw, 2);
        };
        $invoice['vat_mode'] = $this->value('vat_mode');
        if (! isset(Quotes::vat_modes()[$invoice['vat_mode']])) {
            $errors[] = 'รูปแบบ VAT ไม่ถูกต้อง';
            $invoice['vat_mode'] = 'none';
        }
        $invoice['vat_rate'] = $money($this->value('vat_rate'), 'อัตรา VAT ', 30) ?: 7.0;
        $invoice['wht_enabled'] = $this->value('wht_enabled') === '1' ? 1 : 0;
        $invoice['wht_rate'] = $money($this->value('wht_rate'), 'อัตราหัก ณ ที่จ่าย ', 15) ?: 3.0;

        $rows = request()->post('items');
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $text = fn ($k) => is_string($row[$k] ?? null) ? trim($row[$k]) : '';
            if ($text('description') === '' && in_array($text('price'), ['', '0'], true)) {
                continue; // Blank row left in the table.
            }
            $item = ['description' => $text('description'), 'qty' => $money($text('qty'), 'จำนวนในรายการ ', 99999), 'unit' => $text('unit'), 'price' => $money($text('price'), 'ราคาต่อหน่วย ', 99999999), 'disbursement' => $text('disbursement') === '1' ? 1 : 0];
            if ($item['description'] === '' || mb_strlen($item['description']) > 300 || mb_strlen($item['unit']) > 30) {
                $errors[] = 'กรุณากรอกชื่อรายการให้ครบ (ไม่เกิน 300 ตัวอักษร) และหน่วยไม่เกิน 30 ตัวอักษร';
            }
            if ($item['qty'] <= 0) {
                $errors[] = 'จำนวนของ “'.mb_substr($item['description'], 0, 40).'” ต้องมากกว่า 0';
            }
            $items[] = $item;
        }
        if (! $items) {
            $errors[] = 'เพิ่มรายการค่าใช้จ่ายอย่างน้อย 1 รายการ';
        }
        if (count($items) > Invoices::MAX_ITEMS) {
            $errors[] = 'รายการต้องไม่เกิน '.Invoices::MAX_ITEMS.' รายการ';
        }
        $invoice['items'] = $items ?: $invoice['items'];
        $amounts = Invoices::charges($invoice['items'], $invoice['vat_mode'], (float) $invoice['vat_rate'], (bool) $invoice['wht_enabled'], (float) $invoice['wht_rate']);
        if ($items && $amounts['total'] <= 0) {
            $errors[] = 'ยอดรวมใบแจ้งหนี้ต้องมากกว่า 0';
        }

        return [$invoice, $amounts, array_values(array_unique($errors))];
    }
}
