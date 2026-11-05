<?php

namespace App\Http\Controllers;

use finfo;
use Illuminate\Support\Facades\DB;

class PassportsController extends Controller
{
    protected function boot(): void
    {
        set_header('Cache-Control: no-store, private');
        set_header('Referrer-Policy: no-referrer');
        set_header('X-Robots-Tag: noindex, nofollow');
    }

    public function upload($token)
    {
        $this->post_only();
        $b = $this->billing->by_token($token);
        if (! $b) {
            show_404();
        }
        if (! $this->passports->eligible($b)) {
            show_error('เปิดรับพาสปอร์ตหลังอนุมัติยอดมัดจำครบหรือชำระเต็มจำนวนแล้ว', 403);
        }
        $number = filter_var(request()->post('traveler_number'), FILTER_VALIDATE_INT);
        if (! $number || $number < 1 || $number > (int) $b['traveler_count']) {
            show_error('ผู้เดินทางไม่ถูกต้อง', 400);
        }
        // @: a warning here would otherwise become an exception and skip the friendly error.
        $dir = base_path('storage/passports/');
        if (! is_dir($dir) && ! @mkdir($dir, 0750, true)) {
            show_error('ไม่สามารถจัดเก็บไฟล์ได้', 500);
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png', 'max_size' => 5120, 'max_width' => 8000, 'max_height' => 8000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload('passport')) {
            $error = 'กรุณาเลือกภาพ JPG หรือ PNG ขนาดไม่เกิน 5 MB และด้านละไม่เกิน 8,000 พิกเซล';
        } else {
            $f = $this->upload->data();
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['full_path']);
            $error = ! @getimagesize($f['full_path']) || ! in_array($mime, ['image/jpeg', 'image/png'], true) ? 'ไฟล์ต้องเป็นภาพ JPG หรือ PNG ที่ถูกต้อง' : $this->passports->save($b['id'], $number, $f['file_name']);
            if ($error) {
                @unlink($f['full_path']);
            }
        }
        session()->flash($error ? 'error' : 'success', $error ?: 'อัปโหลดพาสปอร์ตผู้เดินทางคนที่ '.$number.' แล้ว');
        redirect_to(base_url().('booking/detail/'.$token.'#passports'));
    }

    public function customer($token, $number)
    {
        $b = $this->billing->by_token($token);
        if (! $b) {
            show_404();
        }

return $this->serve($b['id'], $number);
    }

    public function admin($id, $number)
    {
        $this->require_admin();

        return $this->serve((int) $id, $number);
    }

    private function serve($id, $number)
    {
        $row = DB::table('booking_passports')->where('booking_id', $id)->where('traveler_number', (int) $number)->first();
        $path = $row ? $this->passports->path($row['file_name']) : null;
        if (! $path || ! is_file($path)) {
            show_404();
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            show_404();
        }

        // CI's set_content_type() always appended the configured charset.
        return response(file_get_contents($path), 200, ['Content-Disposition' => 'inline; filename="passport-'.(int) $number.'.'.($mime === 'image/png' ? 'png' : 'jpg').'"', 'Content-Type' => $mime.'; charset=UTF-8']);
    }
}
