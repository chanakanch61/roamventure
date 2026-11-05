<?php

namespace App\Http\Controllers;

use finfo;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    private function customer($token)
    {
        $b = $this->billing->by_token($token);
        if (! $b) {
            show_404();
        }
        set_header('Cache-Control: no-store, private');
        set_header('Referrer-Policy: no-referrer');
        set_header('X-Robots-Tag: noindex, nofollow');

        return $b;
    }

    public function detail($token)
    {
        $b = $this->customer($token);

        return $this->render('booking/detail', ['title' => 'รายละเอียดการจอง '.$b['reference'], 'booking' => $b, 'billing' => $this->billing->state($b)]);
    }

    public function payment($token)
    {
        $b = $this->customer($token);
        if (request_method() === 'post') {
            $action = request()->post('action');
            $error = null;
            if ($action === 'start') {
                $error = $this->billing->start($b['id'], request()->post('kind'));
            } elseif ($action === 'cancel') {
                $error = $this->billing->cancel_attempt($b['id'], (int) request()->post('payment_id'));
            } elseif ($action === 'slip') {
                $fields = [];
                foreach (['reported_amount', 'transferred_at', 'channel'] as $key) {
                    $value = request()->post($key);
                    if (! is_string($value)) {
                        show_error('ข้อมูลไม่ถูกต้อง', 400);
                    } $fields[$key] = $value;
                }
                // @: a failed mkdir only warned in legacy; the upload then reports the unwritable folder.
                $dir = base_path('storage/slips/');
                if (! is_dir($dir)) {
                    @mkdir($dir, 0750, true);
                }
                $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png', 'max_size' => 5120, 'max_width' => 8000, 'max_height' => 8000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
                if (! $this->upload->do_upload('slip')) {
                    $error = 'อัปโหลดสลิป JPG หรือ PNG ไม่เกิน 5 MB: '.strip_tags($this->upload->display_errors());
                } else {
                    $f = $this->upload->data();
                    if (! @getimagesize($f['full_path'])) {
                        $error = 'สลิปต้องเป็นไฟล์รูปภาพ';
                    } else {
                        $error = $this->billing->submit_slip($b['id'], (int) request()->post('payment_id'), $fields, ['path' => 'storage/slips/'.$f['file_name'], 'hash' => hash_file('sha256', $f['full_path'])]);
                    }
                    if ($error) {
                        @unlink($f['full_path']);
                    }
                }
            } else {
                show_error('คำสั่งไม่ถูกต้อง', 400);
            }
            session()->flash($error ? 'error' : 'success', $error ?: ($action === 'slip' ? 'ได้รับสลิปแล้ว รอทีมงานตรวจสอบ' : 'อัปเดตรายการชำระแล้ว'));
            redirect_to(base_url().('booking/payment/'.$token));
        }

        return $this->render('booking/payment', ['title' => 'ชำระเงิน '.$b['reference'], 'booking' => $b, 'billing' => $this->billing->state($b)]);
    }

    public function document($token, $paymentId = 0)
    {
        $b = $this->customer($token);
        $invoice = $this->billing->invoice($b['id']);
        if (! $invoice) {
            show_404();
        }
        $p = $paymentId ? DB::table('payments')->where('booking_id', $b['id'])->where('id', (int) $paymentId)->where('status', 'approved')->first() : null;
        if ($paymentId && ! $p) {
            show_404();
        }

        return $this->render('booking/document', ['title' => $p ? 'ใบเสร็จรับเงิน' : 'ใบแจ้งหนี้', 'booking' => $b, 'invoice' => $invoice, 'payment' => $p]);
    }

    public function slip($token, $paymentId)
    {
        $b = $this->customer($token);
        $p = DB::table('payments')->where('booking_id', $b['id'])->where('id', (int) $paymentId)->first();
        if (! $p || ! $p['slip_path'] || ! preg_match('~^storage/slips/[a-f0-9]+\.(jpg|jpeg|png)$~D', $p['slip_path']) || ! is_file(base_path($p['slip_path']))) {
            show_404();
        }
        $path = base_path($p['slip_path']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        // CI's set_content_type() always appended the configured charset.
        return response(file_get_contents($path),200,['Content-Type' => $mime.'; charset=UTF-8']);
    }
}
