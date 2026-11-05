<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BannersAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        $errors = [];
        $values = [];
        $uploaded = [];
        if (request_method() === 'post') {
            if (request()->post('hero_action') !== null) {
                return $this->save_hero();
            }
            if (request()->post('form_complete') !== '1') {
                show_error('ข้อมูลอัปโหลดไม่ครบ กรุณาลดขนาดไฟล์แล้วลองใหม่', 400);
            }
            $type = request()->post('banner_type');
            if (! is_string($type) || ! isset(page_banner_defaults()[$type])) {
                show_error('ประเภทแบนเนอร์ไม่ถูกต้อง', 400);
            }
            foreach (['title' => 120, 'description' => 400] as $field => $max) {
                $key = 'banner_'.$type.'_'.$field;
                $value = request()->post($field);
                if (! is_string($value) || mb_strlen($value) > $max) {
                    $errors[] = 'ข้อความยาวเกินกำหนดหรือไม่ถูกต้อง';
                } else {
                    $values[$key] = trim($value);
                }
            }
            foreach (['desktop', 'mobile'] as $device) {
                $key = 'banner_'.$type.'_'.$device;
                if (request()->post('remove_'.$device) === '1') {
                    $values[$key] = '';
                }
                $file = $_FILES[$device] ?? null;
                if (! $file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if (! is_string($file['name'] ?? null) || ! is_string($file['tmp_name'] ?? null) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
                    $errors[] = $device.': อัปโหลดไม่สำเร็จ';

                    continue;
                }
                $info = @getimagesize($file['tmp_name']);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $types = ['png' => IMAGETYPE_PNG, 'jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'webp' => IMAGETYPE_WEBP];
                if (! isset($types[$ext]) || ! $info || $info[2] !== $types[$ext] || $info[0] > 8000 || $info[1] > 8000 || $file['size'] > 5 * 1024 * 1024) {
                    $errors[] = $device.': ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px';

                    continue;
                }
                $path = 'public/uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
                if (! @move_uploaded_file($file['tmp_name'], base_path($path))) {
                    $errors[] = 'บันทึกรูปภาพไม่สำเร็จ';

                    continue;
                }
                $uploaded[] = $path;
                $values[$key] = $path;
            }
            if (! $errors) {
                $saved = true;
                DB::beginTransaction();
                try {
                    foreach ($values as $key => $value) {
                        DB::table('settings')->upsert([['setting_key' => $key, 'setting_value' => $value]], ['setting_key'], ['setting_key', 'setting_value']);
                    }
                    DB::commit();
                } catch (QueryException $e) {
                    DB::rollBack();
                    $saved = false;
                }
                if ($saved) {
                    session()->flash('success', 'บันทึกแบนเนอร์แล้ว');
                    redirect_to(base_url().'admin/banners#'.$type);
                }
                $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
            foreach ($uploaded as $path) {
                if (is_file(base_path($path))) {
                    @unlink(base_path($path));
                }
            }
            foreach (['title', 'description'] as $field) {
                $key = 'banner_'.$type.'_'.$field;
                if (isset($values[$key])) {
                    $this->settings[$key] = $values[$key];
                }
            }
            if ($uploaded) {
                $errors[] = 'กรุณาเลือกไฟล์ภาพอีกครั้งหลังแก้ไขข้อมูล';
            }
        }

        return $this->render('admin/banners', ['title' => 'แบนเนอร์เว็บไซต์', 'errors' => $errors], true);
    }

    private function save_hero()
    {
        if (request()->post('form_complete') !== '1') {
            show_error('ข้อมูลอัปโหลดไม่ครบ กรุณาลดขนาดไฟล์แล้วลองใหม่', 400);
        }
        $slides = home_hero_slides($this->settings);
        $errors = [];
        $uploaded = [];
        $id = request()->post('slide_id');
        $action = request()->post('hero_action');
        if (! is_string($id) || ! in_array($action, ['save', 'delete'], true)) {
            show_error('ข้อมูลไม่ถูกต้อง', 400);
        }
        $index = null;
        foreach ($slides as $i => $s) {
            if (($s['id'] ?? null) === $id) {
                $index = $i;
            }
        }
        if ($id !== 'new' && $index === null) {
            show_error('ไม่พบสไลด์', 404);
        }
        if ($action === 'delete') {
            if ($index === null) {
                show_error('ไม่พบสไลด์', 404);
            }
            array_splice($slides, $index, 1);
        } else {
            if ($id === 'new' && count($slides) >= 20) {
                $errors[] = 'เพิ่มได้สูงสุด 20 สไลด์';
            }
            $slide = $index === null ? ['id' => bin2hex(random_bytes(8)), 'desktop' => '', 'tablet' => '', 'mobile' => ''] : $slides[$index];
            foreach (['title' => 120, 'description' => 400, 'eyebrow' => 80] as $field => $max) {
                $value = request()->post($field);
                if (! is_string($value) || mb_strlen($value) > $max) {
                    $errors[] = 'ข้อความไม่ถูกต้องหรือยาวเกินกำหนด';
                } else {
                    $slide[$field] = trim($value);
                }
            }
            if (empty($slide['title'])) {
                $errors[] = 'กรุณากรอกหัวข้อสไลด์';
            }
            $sort = request()->post('sort');
            if (! is_string($sort) || ! preg_match('/^\d{1,4}$/D', $sort)) {
                $errors[] = 'ลำดับต้องเป็นตัวเลข 0–9999';
            }
            $slide['sort'] = (int) $sort;
            $slide['enabled'] = request()->post('enabled') === '1' ? 1 : 0;
            foreach (['desktop', 'tablet', 'mobile'] as $device) {
                if (request()->post('remove_'.$device) === '1') {
                    $slide[$device] = '';
                }
                $file = $_FILES[$device] ?? null;
                if (! $file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if (! is_string($file['name'] ?? null) || ! is_string($file['tmp_name'] ?? null) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
                    $errors[] = $device.': อัปโหลดไม่สำเร็จ';

                    continue;
                }
                $info = @getimagesize($file['tmp_name']);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $types = ['png' => IMAGETYPE_PNG, 'jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'webp' => IMAGETYPE_WEBP];
                if (! isset($types[$ext]) || ! $info || $info[2] !== $types[$ext] || $info[0] > 8000 || $info[1] > 8000 || $file['size'] > 5 * 1024 * 1024) {
                    $errors[] = $device.': ใช้ JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px';

                    continue;
                }
                $path = 'public/uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
                if (! @move_uploaded_file($file['tmp_name'], base_path($path))) {
                    $errors[] = 'บันทึกภาพไม่สำเร็จ';

                    continue;
                }
                $uploaded[] = $path;
                $slide[$device] = $path;
            }
            if (! ($slide['desktop'] ?? null)) {
                $errors[] = 'กรุณาอัปโหลดภาพ Desktop';
            }
            if ($index === null) {
                $slides[] = $slide;
            } else {
                $slides[$index] = $slide;
            }
        }
        if (! $errors) {
            try {
                DB::table('settings')->upsert([['setting_key' => 'home_hero_slides', 'setting_value' => json_encode(array_values($slides), JSON_UNESCAPED_UNICODE)]], ['setting_key'], ['setting_key', 'setting_value']);
            } catch (QueryException $e) {
                $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
        }
        if (! $errors) {
            session()->flash('success', 'บันทึกสไลด์หน้าแรกแล้ว');
            redirect_to(base_url().'admin/banners#home');
        }
        foreach ($uploaded as $path) {
            if (is_file(base_path($path))) {
                @unlink(base_path($path));
            }
        }
        $errors[] = 'ข้อมูลยังไม่ถูกบันทึก กรุณาเลือกไฟล์ภาพอีกครั้ง';

        return $this->render('admin/banners', ['title' => 'แบนเนอร์เว็บไซต์', 'errors' => $errors, 'hero_draft' => ($action === 'save' ? $slide : null), 'hero_draft_id' => $id], true);
    }
}
