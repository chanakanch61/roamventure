<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DestinationsAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    private function kind($kind)
    {
        if (! array_key_exists($kind, $this->places->kinds())) {
            show_404();
        }

return $kind;
    }

    private function upload_onpage_image($field, &$errors)
    {
        if (! isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $dir = base_path('public/uploads/destinations/');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $errors[] = 'ไม่สามารถสร้างที่เก็บรูปภาพ';

            return null;
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 6000, 'max_height' => 6000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload($field)) {
            $errors[] = 'อัปโหลดรูปไม่สำเร็จ: '.strip_tags($this->upload->display_errors());

            return null;
        }
        $file = $this->upload->data();
        if (! @getimagesize($file['full_path'])) {
            @unlink($file['full_path']);
            $errors[] = 'กรุณาเลือกไฟล์รูปภาพ';

            return null;
        }

        return 'public/uploads/destinations/'.$file['file_name'];
    }

    // Authenticated, CSRF-protected POST: image -> {url}; invalid input -> 422 {error}.
    public function upload_image()
    {
        $this->post_only();
        $errors = [];
        $path = $this->upload_onpage_image('image', $errors);
        if (! $path && ! $errors) {
            $errors[] = 'กรุณาเลือกไฟล์รูปภาพ';
        }

        return response(json_encode($errors ? ['error' => implode(' ', $errors)] : ['url' => base_url().$path], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $errors ? 422 : 200, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function clean_content()
    {
        $this->post_only();
        $html = request()->post('content');
        if (! is_string($html) || mb_strlen($html) > 200000) {
            return response(json_encode(['error' => 'เนื้อหาไม่ถูกต้องหรือยาวเกินกำหนด'], JSON_UNESCAPED_UNICODE), 422, ['Content-Type' => 'application/json; charset=UTF-8']);
        }

        return response(json_encode(['html' => $this->places->clean_content($html)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    private function upload_pdf(&$errors)
    {
        $file = $_FILES['source_pdf_file'] ?? null;
        if (! $file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $dir = base_path('storage/destination-pdfs/');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $errors[] = 'ไม่สามารถสร้างที่เก็บ PDF';

            return null;
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'pdf', 'max_size' => 10240, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload('source_pdf_file')) {
            $errors[] = 'อัปโหลด PDF ไม่สำเร็จ: '.strip_tags($this->upload->display_errors());

            return null;
        }
        $file = $this->upload->data();
        if (file_get_contents($file['full_path'], false, null, 0, 5) !== '%PDF-') {
            @unlink($file['full_path']);
            $errors[] = 'ไฟล์ต้นฉบับต้องเป็น PDF';

            return null;
        }

        return 'storage/destination-pdfs/'.$file['file_name'];
    }

    public function source_pdf($kind, $id)
    {
        $this->kind($kind);
        $node = $this->places->find((int) $id);
        $path = $node['source_pdf'] ?? '';
        if (! $node || $node['kind'] !== $kind || ! preg_match('~^storage/destination-pdfs/[a-f0-9]+\.pdf$~D', $path) || ! is_file(base_path($path))) {
            show_404();
        }

        return response(file_get_contents(base_path($path)), 200, ['Content-Type' => 'application/pdf; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="destination-'.(int) $id.'.pdf"']);
    }

    public function index($kind = 'zone')
    {
        $this->kind($kind);
        $rows = DB::select('SELECT n.*,p.name parent_name,p.slug country_slug,(SELECT COUNT(*) FROM destination_nodes ch WHERE ch.parent_id=n.id) child_count,(SELECT COUNT(DISTINCT tr.tour_id) FROM ('.$this->places->links_sql().') tr WHERE tr.route_id=n.id) tour_count FROM destination_nodes n LEFT JOIN destination_nodes p ON p.id=n.parent_id WHERE n.kind=? ORDER BY n.sort_order,n.name', [$kind]);

        return $this->render('admin/destinations', ['title' => 'จัดการจุดหมาย', 'kind' => $kind, 'rows' => $rows, 'kinds' => $this->places->kinds()], true);
    }

    public function edit($kind, $id = 0)
    {
        $this->kind($kind);
        $id = (int) $id;
        $node = $id ? $this->places->find($id) : ['id' => 0, 'kind' => $kind, 'parent_id' => null, 'name' => '', 'slug' => '', 'description' => '', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '', 'sort_order' => 0];
        if (! $node || $node['kind'] !== $kind) {
            show_404();
        } $errors = [];
        $savedNode = $node;
        if (request_method() === 'post') {
            $data = [];
            foreach (['name' => 100, 'slug' => 150, 'description' => 10000, 'meta_title' => 200, 'meta_description' => 300, 'meta_keywords' => 300] as $key => $max) {
                $v = request()->post($key);
                $data[$key] = is_string($v) ? trim($v) : '';
                if (! is_string($v) || mb_strlen($data[$key]) > $max) {
                    $errors[] = $key.' ไม่ถูกต้องหรือยาวเกินกำหนด';
                }
            }
            if ($data['name'] === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $data['slug'])) {
                $errors[] = 'กรอกชื่อและ URL (a-z, 0-9 และขีดกลาง)';
            }
            $parent = filter_var(request()->post('parent_id'), FILTER_VALIDATE_INT);
            $sort = filter_var(request()->post('sort_order'), FILTER_VALIDATE_INT);
            if ($sort === false || $sort < 0 || $sort > 9999) {
                $errors[] = 'ลำดับต้องเป็นจำนวนเต็ม 0–9999';
            }
            if ($kind !== 'zone' && (! $parent || $parent < 1)) {
                $errors[] = 'กรุณาเลือกรายการแม่';
            }
            $data['parent_id'] = $kind === 'zone' ? null : (int) $parent;
            $data['sort_order'] = (int) $sort;
            foreach (['onpage_content' => 200000, 'cover_alt' => 200] as $key => $max) {
                $value = request()->post($key);
                if ($value !== null) {
                    $data[$key] = is_string($value) ? trim($value) : '';
                    if (! is_string($value) || mb_strlen($data[$key]) > $max) {
                        $errors[] = $key.' ไม่ถูกต้องหรือยาวเกินกำหนด';
                    }
                }
            }
            if (isset($data['onpage_content'])) {
                $data['onpage_content'] = $this->places->clean_content($data['onpage_content']);
            }
            $imageSuggestions = request()->post('image_suggestions');
            if ($imageSuggestions !== null) {
                try {
                    if (! is_string($imageSuggestions) || strlen($imageSuggestions) > 30000) {
                        throw new \InvalidArgumentException('ข้อมูลภาพอ้างอิงไม่ถูกต้องหรือยาวเกินกำหนด');
                    }
                    $items = json_decode($imageSuggestions, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \InvalidArgumentException('รูปแบบ JSON ของภาพอ้างอิงไม่ถูกต้อง');
                    }
                    $data['image_suggestions'] = json_encode($this->places->image_suggestions($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } catch (\InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                }
            }
            $uploaded = null;
            $uploadedPdf = null;
            $uploadedCover = null;
            if ($kind === 'country' && ! $errors) {
                if (request()->post('remove_flag') === '1') {
                    $data['flag_path'] = '';
                }
                if (isset($_FILES['flag_file']) && ($_FILES['flag_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $dir = base_path('public/uploads/flags/');
                    if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
                        $errors[] = 'ไม่สามารถสร้างโฟลเดอร์ธงได้';
                    }
                    if (! $errors) {
                        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 2048, 'max_width' => 3000, 'max_height' => 3000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
                        if (! $this->upload->do_upload('flag_file')) {
                            $errors[] = 'อัปโหลดธงไม่สำเร็จ: '.strip_tags($this->upload->display_errors());
                        } else {
                            $f = $this->upload->data();
                            if (! @getimagesize($f['full_path'])) {
                                @unlink($f['full_path']);
                                $errors[] = 'ไฟล์ธงต้องเป็นรูปภาพ';
                            } else {
                                $uploaded = 'public/uploads/flags/'.$f['file_name'];
                                $data['flag_path'] = $uploaded;
                            }
                        }
                    }
                }
            }
            if (! $errors) {
                if (request()->post('remove_cover') === '1') {
                    $data['cover_path'] = '';
                }
                $uploadedCover = $this->upload_onpage_image('cover_file', $errors);
                if ($uploadedCover) {
                    $data['cover_path'] = $uploadedCover;
                }
            }
            if (! $errors) {
                $uploadedPdf = $this->upload_pdf($errors);
                if ($uploadedPdf) {
                    $data['source_pdf'] = $uploadedPdf;
                }
            }
            if (! $errors) {
                $saved = $this->places->save($kind, $id, $data);
                if ($saved) {
                    session()->flash('success', 'บันทึกจุดหมายแล้ว');
                    redirect_to(base_url().('admin/destinations/'.$kind.'/'.$saved));
                } $errors[] = $this->places->error;
            }
            if ($uploaded) {
                $this->places->remove_flag_file($uploaded);
                $errors[] = 'กรุณาเลือกไฟล์ธงใหม่หลังแก้ไขข้อมูล';
            }
            if ($uploadedPdf) {
                $this->places->remove_source_pdf($uploadedPdf);
                $errors[] = 'กรุณาเลือกไฟล์ PDF ใหม่หลังแก้ไขข้อมูล';
            }
            if ($uploadedCover) {
                $this->places->remove_cover_file($uploadedCover);
                $errors[] = 'กรุณาเลือกรูปปกใหม่หลังแก้ไขข้อมูล';
            }
            unset($data['flag_path'],$data['source_pdf'],$data['cover_path']);
            $node = array_merge($node, $data);
        }
        $parents = $kind === 'zone' ? [] : $this->places->all($kind === 'country' ? 'zone' : 'country');
        $existingSlugs = [];
        if (! $id) {
            $slugKinds = $kind === 'route' ? ['route'] : ['zone', 'country'];
            $existingSlugs = array_column(DB::table('destination_nodes')->select('slug')->whereIn('kind', $slugKinds)->get()->all(), 'slug');
            if ($kind !== 'route') {
                foreach (DB::table('tours')->select('code', 'slug')->get()->all() as $tour) {
                    $existingSlugs[] = $tour['code'];
                    $existingSlugs[] = $tour['slug'];
                }
                $existingSlugs = array_merge($existingSlugs, array_column(DB::table('tour_code_aliases')->select('code')->get()->all(), 'code'));
            }
            $existingSlugs = array_values(array_unique(array_map('strtolower', $existingSlugs)));
        }

        return $this->render('admin/destination_form', ['title' => 'จัดการ '.$this->places->kinds()[$kind], 'node' => $node, 'saved_node' => $savedNode, 'kind' => $kind, 'parents' => $parents, 'errors' => $errors, 'kinds' => $this->places->kinds(), 'existing_slugs' => $existingSlugs, 'destination_page' => true], true);
    }

    public function delete($kind, $id)
    {
        $this->post_only();
        $this->kind($kind);
        $ok = $this->places->delete($kind, (int) $id);
        session()->flash($ok ? 'success' : 'error',$ok ? 'ลบรายการแล้ว' : $this->places->error);
        redirect_to(base_url().('admin/destinations/'.$kind));
    }
}
