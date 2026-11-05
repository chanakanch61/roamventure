<?php

namespace App\Http\Controllers;

class AirlinesAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        $airlines = $this->airlines->all();
        foreach ($airlines as &$airline) {
            $airline['usage_count'] = $this->airlines->usages($airline['id']);
        }
        unset($airline);

        return $this->render('admin/airlines', ['title' => 'สายการบิน', 'airlines' => $airlines], true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $airline = $id ? $this->airlines->find($id) : ['id' => 0, 'name' => '', 'code' => '', 'logo_path' => '', 'is_active' => 1, 'sort_order' => 0];
        if (! $airline) {
            show_404();
        }
        $errors = [];
        if (request_method() === 'post') {
            if (request()->post('form_complete') !== '1') {
                show_error('ข้อมูลอัปโหลดไม่ครบ กรุณาลดขนาดไฟล์แล้วลองใหม่', 400);
            }
            $name = request()->post('name');
            $code = request()->post('code');
            $sort = filter_var(request()->post('sort_order'), FILTER_VALIDATE_INT);
            $name = is_string($name) ? trim($name) : '';
            $code = is_string($code) ? strtoupper(trim($code)) : '';
            if ($name === '' || mb_strlen($name) > 120) {
                $errors[] = 'กรอกชื่อสายการบิน 1–120 ตัวอักษร';
            }
            if ($code !== '' && ! preg_match('/^[A-Z0-9]{2,3}$/D', $code)) {
                $errors[] = 'รหัสสายการบินต้องเป็นตัวอักษรอังกฤษหรือตัวเลข 2–3 ตัว';
            }
            if ($sort === false || $sort < 0 || $sort > 9999) {
                $errors[] = 'ลำดับแสดงผลต้องเป็น 0–9999';
            }
            $data = ['name' => $name, 'code' => $code, 'logo_path' => $airline['logo_path'], 'is_active' => request()->post('is_active') === '1' ? 1 : 0, 'sort_order' => $sort === false ? 0 : $sort];
            if (request()->post('remove_logo') === '1') {
                $data['logo_path'] = '';
            }
            $uploaded = $errors ? '' : $this->upload_logo($errors);
            if ($uploaded) {
                $data['logo_path'] = $uploaded;
            }
            if (! $errors) {
                $saved = $this->airlines->save($id, $data);
                if ($saved) {
                    if ($airline['logo_path'] !== $data['logo_path']) {
                        $this->airlines->remove_logo_file($airline['logo_path']);
                    }
                    session()->flash('success', 'บันทึกสายการบินแล้ว');
                    redirect_to(base_url().'admin/airlines');
                }
                $errors[] = $this->airlines->error;
            }
            if ($uploaded) {
                $this->airlines->remove_logo_file($uploaded);
                $data['logo_path'] = $airline['logo_path'];
                $errors[] = 'กรุณาเลือกไฟล์โลโก้อีกครั้งหลังแก้ไขข้อมูล';
            }
            $airline = array_merge($airline, $data);
        }

        return $this->render('admin/airline_form', ['title' => $id ? 'แก้ไขสายการบิน' : 'เพิ่มสายการบิน', 'airline' => $airline, 'errors' => $errors], true);
    }

    public function delete($id)
    {
        $this->post_only();
        $ok = $this->airlines->delete((int) $id);
        session()->flash($ok ? 'success' : 'error', $ok ? 'ลบสายการบินแล้ว' : $this->airlines->error);
        redirect_to(base_url().'admin/airlines');
    }

    private function upload_logo(&$errors)
    {
        if (($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        $dir = base_path('public/uploads/airlines/');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $errors[] = 'ไม่สามารถสร้างโฟลเดอร์โลโก้ได้';

            return '';
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 1024, 'max_width' => 3000, 'max_height' => 3000, 'encrypt_name' => true]);
        if (! $this->upload->do_upload('logo_file')) {
            $errors[] = 'อัปโหลดโลโก้ไม่สำเร็จ: ใช้ PNG, WebP หรือ JPG ไม่เกิน 1 MB';

            return '';
        }

        return 'public/uploads/airlines/'.$this->upload->data()['file_name'];
    }
}
