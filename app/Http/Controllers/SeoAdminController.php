<?php

namespace App\Http\Controllers;

use App\Support\ShareImage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeoAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        return $this->render('admin/seo_pages', ['title' => 'Meta Tag หน้าเว็บ'], true);
    }

    public function edit($key)
    {
        $page = seo_pages()[$key] ?? null;
        if (! $page) {
            show_404();
        }
        $errors = [];
        if (request_method() === 'post') {
            if (request()->post('form_complete') !== '1') {
                show_error('ข้อมูลอัปโหลดไม่ครบ กรุณาลดขนาดไฟล์แล้วลองใหม่', 400);
            }
            $values = [];
            foreach (['title' => 200, 'description' => 300, 'keywords' => 300] as $field => $max) {
                $value = request()->post($field);
                if (! is_string($value) || mb_strlen($value) > $max) {
                    $errors[] = 'ข้อความยาวเกินกำหนดหรือไม่ถูกต้อง';
                } else {
                    $values['seo_'.$key.'_'.$field] = trim((string) preg_replace('/\s+/u', ' ', $value));
                }
            }
            $imageKey = 'seo_'.$key.'_image';
            $oldImage = branding_path($this->settings, $imageKey);
            $newImage = '';
            if (request()->post('remove_image') === '1') {
                $values[$imageKey] = '';
            }
            $file = $_FILES['image'] ?? null;
            if (! $errors && $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                try {
                    $newImage = ShareImage::store($file);
                    $values[$imageKey] = $newImage;
                } catch (RuntimeException $e) {
                    $errors[] = $e->getMessage();
                }
            }
            if (! $errors) {
                DB::beginTransaction();
                try {
                    foreach ($values as $setting => $value) {
                        DB::table('settings')->upsert([['setting_key' => $setting, 'setting_value' => $value]], ['setting_key'], ['setting_key', 'setting_value']);
                    }
                    DB::commit();
                    // Every upload gets a new file name, so nothing else can still point at the old one.
                    if ($oldImage && ($values[$imageKey] ?? $oldImage) !== $oldImage) {
                        @unlink(base_path($oldImage));
                    }
                    session()->flash('success', 'บันทึก Meta Tag ของ'.$page['label'].'แล้ว');
                    redirect_to(base_url().'admin/seo/'.$key);
                } catch (QueryException $e) {
                    DB::rollBack();
                    $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
                    if ($newImage && is_file(base_path($newImage))) {
                        @unlink(base_path($newImage));
                    }
                }
            }
            unset($values[$imageKey]);
            $this->settings = array_merge($this->settings, $values);
        }

        return $this->render('admin/seo_page_form', ['title' => 'Meta Tag: '.$page['label'], 'seo_key' => $key, 'seo_page' => $page, 'seo_defaults' => seo_page_defaults($this->settings, $key), 'errors' => $errors], true);
    }
}
