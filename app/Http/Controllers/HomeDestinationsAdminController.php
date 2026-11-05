<?php

namespace App\Http\Controllers;

use App\Repositories\HomeDestinationRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class HomeDestinationsAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        $config = $this->home_places->config($this->settings);
        $choices = $this->home_places->choices();
        $errors = [];
        $uploaded = [];
        if (request_method() === 'post') {
            if (request()->post('form_complete') !== '1') {
                show_error('ข้อมูลไม่ครบ กรุณาลดขนาดไฟล์แล้วลองใหม่', 400);
            }
            $draft = $config;
            $draft['enabled'] = request()->post('enabled') === '1' ? 1 : 0;
            $draft['mode'] = request()->post('mode');
            if (! in_array($draft['mode'], ['auto', 'manual'], true)) {
                $errors[] = 'กรุณาเลือกโหมดแสดงผล';
                $draft['mode'] = $config['mode'];
            }
            foreach (['eyebrow' => 80, 'title' => 120, 'description' => 400] as $field => $max) {
                $value = request()->post($field);
                if (! is_string($value) || mb_strlen($value) > $max) {
                    $errors[] = 'ข้อความไม่ถูกต้องหรือยาวเกินกำหนด';
                } else {
                    $draft[$field] = trim($value);
                }
            }
            if ($draft['title'] === '') {
                $errors[] = 'กรุณากรอกหัวข้อ';
            }
            $rows = request()->post('cards');
            $order = request()->post('card_order');
            if (! is_array($rows) || count($rows) !== 4 || ! is_string($order) || ! preg_match('/^[0-3](,[0-3]){3}$/D', $order) || count(array_unique(explode(',', $order))) !== 4) {
                show_error('ข้อมูลการ์ดไม่ถูกต้อง', 400);
            }
            $known = array_column($choices, null, 'id');
            $seen = [];
            $draft['cards'] = [];
            foreach (explode(',', $order) as $slot) {
                $row = $rows[$slot] ?? null;
                if (! is_array($row)) {
                    show_error('ข้อมูลการ์ดไม่ถูกต้อง', 400);
                }
                $node = filter_var($row['node_id'] ?? '', FILTER_VALIDATE_INT);
                $old = $config['cards'][(int) $slot] ?? [];
                if (($row['node_id'] ?? '') !== '' && (! $node || ! isset($known[$node]))) {
                    $errors[] = 'จุดหมายที่เลือกไม่มีอยู่แล้ว กรุณาเลือกใหม่';
                }
                if ($node && in_array($node, $seen, true)) {
                    $errors[] = 'กรุณาเลือกจุดหมายไม่ซ้ำกัน';
                }
                if ($node) {
                    $seen[] = $node;
                }
                $card = ['node_id' => $node ?: 0, 'enabled' => ($row['enabled'] ?? '') === '1' ? 1 : 0, 'image' => ($row['remove_image'] ?? '') === '1' ? '' : ($old['image'] ?? '')];
                $file = $_FILES['image_'.$slot] ?? null;
                if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    if (! is_string($file['name'] ?? null) || ! is_string($file['tmp_name'] ?? null) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
                        $errors[] = 'อัปโหลดรูปไม่สำเร็จ';
                    } else {
                        $info = @getimagesize($file['tmp_name']);
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $types = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'webp' => IMAGETYPE_WEBP];
                        if (! isset($types[$ext]) || ! $info || $info[2] !== $types[$ext] || $info[0] > 8000 || $info[1] > 8000 || $file['size'] > 5 * 1024 * 1024) {
                            $errors[] = 'ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px';
                        } else {
                            $path = 'public/uploads/destination-'.bin2hex(random_bytes(16)).'.'.$ext;
                            if (! @move_uploaded_file($file['tmp_name'], base_path($path))) {
                                $errors[] = 'บันทึกรูปภาพไม่สำเร็จ';
                            } else {
                                $uploaded[] = $path;
                                $card['image'] = $path;
                            }
                        }
                    }
                }
                $draft['cards'][] = $card;
            }
            if (! $errors) {
                $saved = true;
                DB::beginTransaction();
                try {
                    DB::table('settings')->upsert([['setting_key' => HomeDestinationRepository::KEY, 'setting_value' => json_encode($draft, JSON_UNESCAPED_UNICODE)]], ['setting_key'], ['setting_key', 'setting_value']);
                    DB::commit();
                } catch (QueryException $e) {
                    DB::rollBack();
                    $saved = false;
                }
                if ($saved) {
                    session()->flash('success', 'บันทึกจุดหมายแนะนำหน้าแรกแล้ว');
                    redirect_to(base_url().'admin/home-destinations');
                }
                $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
            foreach ($uploaded as $path) {
                if (is_file(base_path($path))) {
                    @unlink(base_path($path));
                }
            }
            $draft['cards'] = $config['cards'];
            $config = $draft;
            $errors[] = 'ยังไม่ได้บันทึกการ์ด กรุณาเลือกจุดหมายและจัดลำดับอีกครั้ง';
            if ($uploaded) {
                $errors[] = 'กรุณาเลือกไฟล์ภาพใหม่หลังแก้ไขข้อมูล';
            }
        }

        return $this->render('admin/home_destinations', ['title' => 'จุดหมายแนะนำหน้าแรก', 'config' => $config, 'choices' => $choices, 'errors' => $errors], true);
    }
}
