<?php

namespace App\Http\Controllers;

use App\Support\VehicleOptions;

class VehicleTemplatesAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        return $this->render('admin/vehicle_templates', ['title' => 'Template รถ', 'templates' => $this->vehicle_templates->all()], true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $template = $id ? $this->vehicle_templates->find($id) : ['id' => 0, 'name' => '', 'vehicle_rows' => null];
        if (! $template) {
            show_404();
        }
        $errors = [];
        if (request_method() === 'post') {
            $name = $this->posted_name($errors);
            $oldImages = VehicleOptions::images(VehicleOptions::decode($template['vehicle_rows']));
            $rows = VehicleOptions::parse(request()->post('vehicle_options'), $errors, '', $oldImages);
            if (! $rows) {
                $errors[] = 'กรุณาเพิ่มรถอย่างน้อย 1 แถว';
            }
            if (! $errors) {
                if ($this->vehicle_templates->save($id, $name, $rows)) {
                    session()->flash('success', 'บันทึก template แล้ว');
                    redirect_to(base_url().'admin/vehicle-templates');
                }
                $errors[] = $this->vehicle_templates->error;
            }
            $template = ['id' => $id, 'name' => $name, 'vehicle_rows' => VehicleOptions::encode($rows)];
        }

        return $this->render('admin/vehicle_template_form', ['title' => $id ? 'แก้ไข template รถ' : 'เพิ่ม template รถ', 'template' => $template, 'errors' => $errors], true);
    }

    public function delete($id)
    {
        $this->post_only();
        $ok = $this->vehicle_templates->delete((int) $id);
        session()->flash($ok ? 'success' : 'error', $ok ? 'ลบ template แล้ว โปรแกรมที่ดึงไปใช้ยังแสดงตารางรถเดิม' : $this->vehicle_templates->error);
        redirect_to(base_url().'admin/vehicle-templates');
    }

    public function photo()
    {
        if (request_method() !== 'post') {
            return $this->json(['error' => 'ไม่รองรับคำขอนี้'], 405);
        }
        $failed = ['error' => 'ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px'];
        $dir = base_path(VehicleOptions::TEMPLATE_FOLDER);
        if (empty($_FILES['photo']['name']) || (! is_dir($dir) && ! @mkdir($dir, 0755, true))) {
            return $this->json($failed, 422);
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 8000, 'max_height' => 8000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload('photo')) {
            return $this->json($failed, 422);
        }
        $file = $this->upload->data();
        if (! @getimagesize($file['full_path'])) {
            @unlink($file['full_path']);

            return $this->json($failed, 422);
        }

        return $this->json(['path' => VehicleOptions::TEMPLATE_FOLDER.$file['file_name']], 200);
    }

    // The save button on a program: rows come from the form as typed, before the program itself is saved.
    public function from_program()
    {
        if (request_method() !== 'post') {
            return $this->json(['error' => 'ไม่รองรับคำขอนี้'], 405);
        }
        $folder = request()->post('folder');
        $known = is_string($folder) && (preg_match('/^new-[a-f0-9]{16}$/D', $folder) || (ctype_digit($folder) && $this->trips->find((int) $folder)));
        $posted = request()->post('rows');
        $errors = [];
        $name = $this->posted_name($errors);
        $rows = VehicleOptions::parse(is_string($posted) ? json_decode($posted, true) : null, $errors, $known ? $folder : '');
        if (! $rows) {
            $errors[] = 'กรุณาเพิ่มรถอย่างน้อย 1 แถว';
        }
        if ($errors) {
            return $this->json(['error' => implode(' · ', array_unique($errors))], 422);
        }
        $copied = [];
        foreach ($rows as &$row) {
            if (! empty($row['image'])) {
                $row['image'] = $this->vehicle_templates->copy_image($row['image']);
                if (! $row['image']) {
                    array_map(static fn ($path) => @unlink(base_path($path)), $copied);

                    return $this->json(['error' => 'คัดลอกรูปรถไม่สำเร็จ กรุณาลองใหม่'], 422);
                }
                $copied[] = $row['image'];
            }
        }
        unset($row);
        $id = $this->vehicle_templates->save(0, $name, $rows);
        if (! $id) {
            array_map(static fn ($path) => @unlink(base_path($path)), $copied);

            return $this->json(['error' => $this->vehicle_templates->error], 422);
        }

        return $this->json(['id' => $id, 'name' => $name, 'rows' => $rows], 200);
    }

    private function posted_name(&$errors)
    {
        $name = request()->post('name');
        $name = is_string($name) ? trim($name) : '';
        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = 'กรอกชื่อ template 1–120 ตัวอักษร';
        }

        return $name;
    }

    private function json($body, $status)
    {
        return response(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
