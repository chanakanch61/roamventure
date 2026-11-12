<?php

namespace App\Http\Controllers;

use App\Repositories\HotelLibraryRepository;

class HotelLibraryAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    public function index()
    {
        return $this->render('admin/hotel_library', ['title' => 'คลังโรงแรม', 'hotels' => $this->hotel_library->all()], true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $hotel = $id ? $this->hotel_library->find($id) : ['id' => 0, 'name' => '', 'images' => []];
        if (! $hotel) {
            show_404();
        }
        $errors = [];
        if (request_method() === 'post') {
            $name = $this->posted_name($errors);
            $posted = request()->post('images_json');
            $images = $this->posted_images(is_string($posted) ? json_decode($posted, true) : null, HotelLibraryRepository::IMAGE, $errors);
            if (! $errors) {
                if ($this->hotel_library->save($id, $name, $images)) {
                    session()->flash('success', 'บันทึกโรงแรมในคลังแล้ว');
                    redirect_to(base_url().'admin/hotel-library');
                }
                $errors[] = $this->hotel_library->error;
            }
            $hotel = ['id' => $id, 'name' => $name, 'images' => $images];
        }

        return $this->render('admin/hotel_library_form', ['title' => $id ? 'แก้ไขโรงแรมในคลัง' : 'เพิ่มโรงแรมในคลัง', 'hotel' => $hotel, 'errors' => $errors, 'hotel_library_page' => true], true);
    }

    public function delete($id)
    {
        $this->post_only();
        $ok = $this->hotel_library->delete((int) $id);
        session()->flash($ok ? 'success' : 'error', $ok ? 'ลบโรงแรมออกจากคลังแล้ว โปรแกรมที่ใช้โรงแรมนี้ยังแสดงเหมือนเดิม' : $this->hotel_library->error);
        redirect_to(base_url().'admin/hotel-library');
    }

    public function photo()
    {
        if (request_method() !== 'post') {
            return $this->json(['error' => 'ไม่รองรับคำขอนี้'], 405);
        }
        $failed = ['error' => 'ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px'];
        $dir = base_path(HotelLibraryRepository::FOLDER);
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

        return $this->json(['path' => HotelLibraryRepository::FOLDER.$file['file_name']], 200);
    }

    // The save button on a program's hotel card: the hotel comes from the form as typed, before the program itself is saved.
    public function from_program()
    {
        if (request_method() !== 'post') {
            return $this->json(['error' => 'ไม่รองรับคำขอนี้'], 405);
        }
        $errors = [];
        $name = $this->posted_name($errors);
        $posted = request()->post('images');
        $images = $this->posted_images(is_string($posted) ? json_decode($posted, true) : null, HotelLibraryRepository::PROGRAM_IMAGE, $errors);
        if ($errors) {
            return $this->json(['error' => implode(' · ', array_unique($errors))], 422);
        }
        // Only files this request created are cleaned up on failure; a library photo reused as is stays.
        $copied = [];
        foreach ($images as &$path) {
            $copy = $this->hotel_library->copy_image($path);
            if (! $copy) {
                array_map(static fn ($file) => @unlink(base_path($file)), $copied);

                return $this->json(['error' => 'คัดลอกรูปโรงแรมไม่สำเร็จ กรุณาลองใหม่'], 422);
            }
            if ($copy !== $path) {
                $copied[] = $copy;
            }
            $path = $copy;
        }
        unset($path);
        $id = $this->hotel_library->save(0, $name, $images);
        if (! $id) {
            array_map(static fn ($file) => @unlink(base_path($file)), $copied);

            return $this->json(['error' => $this->hotel_library->error], 422);
        }

        return $this->json(['id' => (int) $id, 'name' => $name, 'images' => $images], 200);
    }

    private function posted_name(&$errors)
    {
        $name = request()->post('name');
        $name = is_string($name) ? trim($name) : '';
        if ($name === '' || mb_strlen($name) > 200) {
            $errors[] = 'กรอกชื่อโรงแรม 1–200 ตัวอักษร';
        }

        return $name;
    }

    private function posted_images($posted, $pattern, &$errors)
    {
        $images = [];
        foreach (is_array($posted) ? $posted : [] as $path) {
            if (! is_string($path) || ! preg_match($pattern, $path) || ! is_file(base_path($path))) {
                $errors[] = 'รูปโรงแรมไม่ถูกต้อง กรุณาอัปโหลดใหม่';

                continue;
            }
            $images[] = $path;
        }

        return array_values(array_unique($images));
    }

    private function json($body, $status)
    {
        return response(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
