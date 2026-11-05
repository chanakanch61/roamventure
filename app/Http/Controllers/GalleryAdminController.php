<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GalleryAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    private function value($key)
    {
        $v = request()->post($key);

        return is_string($v) ? trim($v) : '';
    }

    public function index()
    {
        $filters = [];
        foreach (['q', 'category', 'status', 'page'] as $k) {
            $v = request()->query($k);
            $filters[$k] = is_string($v) ? mb_substr(trim($v), 0, 180) : '';
        }

        return $this->render('admin/gallery_index', array_merge($this->gallery->listing($filters, true), ['title' => 'จัดการ Gallery', 'gallery_page' => true, 'filters' => $filters, 'categories' => $this->gallery->categories(true)]), true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $album = $id ? $this->gallery->find($id) : ['id' => 0, 'title' => '', 'category' => '', 'location' => '', 'description' => '', 'status' => 'draft', 'sort_order' => 0, 'revision' => 0];
        if (! $album) {
            show_404();
        }$photos = $id ? $this->gallery->photos($id) : [];
        $errors = [];
        $uploads = [];
        $original = $album;
        if (request_method() === 'post') {
            foreach (['title' => 180, 'category' => 80, 'location' => 180, 'description' => 5000] as $k => $max) {
                $album[$k] = $this->value($k);
                if (mb_strlen($album[$k]) > $max) {
                    $errors[] = 'ข้อความยาวเกินกำหนด: '.$k;
                }
            }
            if (! $album['title']) {
                $errors[] = 'กรุณากรอกชื่ออัลบั้ม';
            }
            $album['status'] = $this->value('status');
            if (! in_array($album['status'], ['draft', 'published'], true)) {
                $errors[] = 'สถานะไม่ถูกต้อง';
            }
            $sort = filter_var($this->value('sort_order'), FILTER_VALIDATE_INT);
            if ($sort === false || $sort < 0 || $sort > 9999) {
                $errors[] = 'ลำดับต้องอยู่ระหว่าง 0–9999';
            } else {
                $album['sort_order'] = $sort;
            }
            $rows = request()->post('photos');
            if (! is_array($rows)) {
                $rows = [];
            }$kept = [];
            $removed = [];
            $cover = $this->value('cover');
            foreach ($photos as $p) {
                $r = $rows[$p['id']] ?? [];
                if (! is_array($r)) {
                    $r = [];
                }
                if (($r['remove'] ?? '') === '1') {
                    $removed[] = $p['path'];

                    continue;
                }
                $caption = is_string($r['caption'] ?? null) ? trim($r['caption']) : $p['caption'];
                $order = filter_var($r['sort_order'] ?? $p['sort_order'], FILTER_VALIDATE_INT);
                if (mb_strlen($caption) > 250 || $order === false || $order < 0 || $order > 9999) {
                    $errors[] = 'คำบรรยายหรือลำดับภาพไม่ถูกต้อง';
                }
                $p['caption'] = $caption;
                $p['sort_order'] = $order;
                $p['is_cover'] = $cover === (string) $p['id'] ? 1 : 0;
                $kept[] = $p;
            }
            $files = $_FILES['images'] ?? null;
            $count = 0;
            if ($files && is_array($files['name'])) {
                foreach ($files['error'] as $err) {
                    if ($err !== UPLOAD_ERR_NO_FILE) {
                        $count++;
                    }
                }
            }
            if (count($kept) + $count > 60 || $count > 15) {
                $errors[] = 'อัปโหลดครั้งละไม่เกิน 15 ภาพ และไม่เกิน 60 ภาพต่ออัลบั้ม';
            }
            if (! $errors && $count) {
                $dir = base_path('public/uploads/gallery/');
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                foreach ($files['name'] as $i => $name) {
                    if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $_FILES['gallery_file'] = array_combine(['name', 'type', 'tmp_name', 'error', 'size'], array_map(function ($k) use ($files, $i) {
                        return $files[$k][$i];
                    }, ['name', 'type', 'tmp_name', 'error', 'size']));
                    $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 6000, 'max_height' => 6000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
                    if (! $this->upload->do_upload('gallery_file')) {
                        $errors[] = 'อัปโหลดไม่สำเร็จ: '.strip_tags($this->upload->display_errors());
                        break;
                    }
                    $f = $this->upload->data();
                    $path = 'public/uploads/gallery/'.$f['file_name'];
                    $uploads[] = $path;
                    if (! @getimagesize($f['full_path'])) {
                        $errors[] = 'ไฟล์ต้องเป็นรูปภาพเท่านั้น';
                        break;
                    }
                    $kept[] = ['path' => $path, 'caption' => '', 'sort_order' => count($kept), 'is_cover' => 0];
                }
            }
            if ($album['status'] === 'published' && ! $kept) {
                $errors[] = 'เพิ่มอย่างน้อย 1 ภาพก่อนเผยแพร่';
            }
            if (! $errors) {
                DB::beginTransaction();
                if ($id) {
                    $locked = DB::select('SELECT revision FROM gallery_albums WHERE id=? FOR UPDATE', [$id])[0] ?? null;
                    if (! $locked || (string) $locked['revision'] !== $this->value('revision')) {
                        $errors[] = 'มีการแก้ไขอัลบั้มจากหน้าต่างอื่น กรุณาโหลดหน้าใหม่ก่อนบันทึก';
                    }
                }
                if (! $errors) {
                    try {
                        $data = $album;
                        unset($data['id']);
                        $data['revision'] = $original['revision'] + 1;
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        if ($id) {
                            DB::table('gallery_albums')->where('id', $id)->update($data);
                        } else {
                            $data['created_at'] = $data['updated_at'];
                            $id = (int) DB::table('gallery_albums')->insertGetId($data);
                        }
                        if ($kept && ! array_sum(array_column($kept, 'is_cover'))) {
                            $kept[0]['is_cover'] = 1;
                        }
                        $keepIds = [];
                        foreach ($kept as $p) {
                            $photoId = $p['id'] ?? 0;
                            unset($p['id']);
                            $p['album_id'] = $id;
                            if ($photoId) {
                                DB::table('gallery_photos')->where('id', $photoId)->where('album_id', $id)->update($p);
                            } else {
                                $photoId = DB::table('gallery_photos')->insertGetId($p);
                            }$keepIds[] = $photoId;
                        }
                        $query = DB::table('gallery_photos')->where('album_id', $id);
                        if ($keepIds) {
                            $query->whereNotIn('id', $keepIds);
                        }$query->delete();
                    } catch (QueryException $e) {
                        $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
                    }
                }
                if ($errors) {
                    DB::rollBack();
                } else {
                    DB::commit();
                    $this->gallery->remove_files($removed);
                    session()->flash('success', 'บันทึกอัลบั้มเรียบร้อยแล้ว');
                    redirect_to(base_url().'admin/gallery/'.$id);
                }
            }
            $this->gallery->remove_files($uploads);
        }

        return $this->render('admin/gallery_edit', ['title' => $album['id'] ? 'แก้ไขอัลบั้ม' : 'สร้างอัลบั้มใหม่', 'gallery_page' => true, 'album' => $album, 'photos' => $photos, 'errors' => $errors, 'categories' => $this->gallery->categories(true)], true);
    }

    public function delete($id)
    {
        $this->post_only();
        DB::beginTransaction();
        $album = DB::select('SELECT * FROM gallery_albums WHERE id=? FOR UPDATE', [(int) $id])[0] ?? null;
        if (! $album) {
            DB::rollBack();
            show_404();
        }
        if ((string) $album['revision'] !== $this->value('revision')) {
            DB::rollBack();
            show_error('อัลบั้มมีการเปลี่ยนแปลง กรุณาโหลดหน้าใหม่', 409);
        }
        $paths = array_column($this->gallery->photos($id), 'path');
        try {
            DB::table('gallery_albums')->where('id', (int) $id)->delete();
        } catch (QueryException $e) {
            DB::rollBack();
            show_error('ลบไม่สำเร็จ', 500);
        }
        DB::commit();
        $this->gallery->remove_files($paths);
        session()->flash('success','ลบอัลบั้มเรียบร้อยแล้ว');
        redirect_to(base_url().'admin/gallery');
    }
}
