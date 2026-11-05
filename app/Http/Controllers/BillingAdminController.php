<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BillingAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function review($id)
    {
        $this->post_only();
        $p = DB::table('payments')->where('id', (int) $id)->first();
        if (! $p) {
            show_404();
        }
        $note = request()->post('review_note');
        if (! is_string($note)) {
            show_error('ข้อมูลไม่ถูกต้อง', 400);
        }
        $error = $this->billing->review($p['booking_id'], $p['id'], request()->post('decision'), $note, request()->post('ack_difference') === '1');
        session()->flash($error ? 'error' : 'success', $error ?: 'บันทึกผลตรวจสลิปแล้ว');
        redirect_to(base_url().('admin/bookings/'.$p['booking_id']));
    }

    public function extend($id)
    {
        $this->post_only();
        $hours = filter_var(request()->post('hours'), FILTER_VALIDATE_INT);
        $error = $this->billing->extend((int) $id, $hours === false ? 0 : $hours);
        session()->flash($error ? 'error' : 'success', $error ?: 'ปรับกำหนดชำระแล้ว');
        redirect_to(base_url().('admin/bookings/'.(int) $id));
    }

    public function settings()
    {
        $errors = [];
        if (request_method() === 'post') {
            $values = [];
            foreach (['company_address', 'company_tax_id', 'bank_name', 'bank_account', 'bank_holder', 'promptpay_number'] as $key) {
                $v = request()->post($key);
                if (! is_string($v) || mb_strlen($v) > 1000) {
                    $errors[] = 'ข้อมูลไม่ถูกต้องหรือยาวเกินกำหนด';
                } else {
                    $values[$key] = trim($v);
                }
            }
            if (! $errors && (! empty($values['bank_account']) || ! empty($values['promptpay_number']) || ! empty($_FILES['payment_qr']['name'])) && empty($values['bank_holder'])) {
                $errors[] = 'กรุณาระบุชื่อผู้รับเงิน';
            }
            if (! $errors && ! empty($_FILES['payment_qr']['name'])) {
                $this->upload->initialize(['upload_path' => base_path('public/uploads/'), 'allowed_types' => 'jpg|jpeg|png', 'max_size' => 5120, 'max_width' => 8000, 'max_height' => 8000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
                if (! $this->upload->do_upload('payment_qr')) {
                    $errors[] = strip_tags($this->upload->display_errors());
                } else {
                    $f = $this->upload->data();
                    if (! @getimagesize($f['full_path'])) {
                        @unlink($f['full_path']);
                        $errors[] = 'QR ต้องเป็นรูปภาพ';
                    } else {
                        $values['payment_qr'] = 'public/uploads/'.$f['file_name'];
                    }
                }
            }
            if (! $errors) {
                if (request()->post('remove_qr') === '1' && empty($values['payment_qr'])) {
                    $values['payment_qr'] = '';
                }
                // In legacy production, with no transaction here, a failed write was ignored.
                foreach ($values as $k => $v) {
                    try {
                        DB::table('settings')->upsert([['setting_key' => $k, 'setting_value' => $v]], ['setting_key'], ['setting_key', 'setting_value']);
                    } catch (QueryException $e) {
                    }
                }
                session()->flash('success', 'บันทึกช่องทางรับชำระแล้ว');
                redirect_to(base_url().('admin/payment-settings'));
            }
        }

        return $this->render('admin/payment_settings', ['title' => 'ช่องทางรับชำระเงิน', 'errors' => $errors], true);
    }
}
