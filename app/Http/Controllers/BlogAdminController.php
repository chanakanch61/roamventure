<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BlogAdminController extends Controller
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

    public function clean_content()
    {
        $this->post_only();
        $html = $this->value('content');
        if (mb_strlen($html) > 200000) {
            return response(json_encode(['error' => 'เนื้อหายาวเกินกำหนด']), 422, ['Content-Type' => 'application/json; charset=UTF-8']);
        }

        return response(json_encode(['html' => $this->blog->clean_html($html)], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function upload_image()
    {
        $this->post_only();
        $dir = base_path('public/uploads/blog/');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return response(json_encode(['error' => 'ไม่สามารถสร้างที่เก็บรูปภาพ']), 500, ['Content-Type' => 'application/json; charset=UTF-8']);
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 6000, 'max_height' => 6000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload('image')) {
            return response(json_encode(['error' => 'อัปโหลดไม่สำเร็จ: '.strip_tags($this->upload->display_errors())], JSON_UNESCAPED_UNICODE), 422, ['Content-Type' => 'application/json; charset=UTF-8']);
        }
        $file = $this->upload->data();
        if (! @getimagesize($file['full_path'])) {
            @unlink($file['full_path']);

            return response(json_encode(['error' => 'กรุณาเลือกไฟล์รูปภาพ']), 422, ['Content-Type' => 'application/json; charset=UTF-8']);
        }

        return response(json_encode(['url' => base_url().'public/uploads/blog/'.$file['file_name']], JSON_UNESCAPED_SLASHES), 200, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function index()
    {
        $filters = [];
        foreach (['q', 'status', 'category', 'page'] as $key) {
            $v = request()->query($key);
            $filters[$key] = is_string($v) ? mb_substr(trim($v), 0, 200) : '';
        }
        if (! in_array($filters['status'], ['draft', 'published', 'trash'], true)) {
            $filters['status'] = '';
        }
        $counts = array_fill_keys(['draft', 'published', 'trash'], 0);
        foreach (DB::table('blog_posts')->selectRaw('status,COUNT(*) n')->groupBy('status')->get()->all() as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }

        return $this->render('admin/blog_index', array_merge($this->blog->listing($filters, false), ['title' => 'บทความ Blog', 'blog_page' => true, 'filters' => $filters, 'counts' => $counts, 'categories' => $this->blog->categories()]), true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $post = $id ? $this->blog->find($id) : array_fill_keys(['title', 'slug', 'excerpt', 'content', 'cover', 'cover_alt', 'tags', 'seo_title', 'seo_description'], '');
        if (! $post) {
            show_404();
        }if (! $id) {
            $post += ['id' => 0, 'status' => 'draft', 'category_id' => null, 'published_at' => null, 'author' => session('admin_name'), 'updated_at' => ''];
        }
        if ($post['status'] === 'trash') {
            session()->flash('error', 'กู้คืนบทความก่อนแก้ไข');
            redirect_to(base_url().'admin/blog?status=trash');
        }
        $errors = [];
        $original = $post;
        if (request_method() === 'post') {
            foreach (['title' => 200, 'slug' => 180, 'excerpt' => 1500, 'content' => 200000, 'cover_alt' => 200, 'tags' => 500, 'author' => 100, 'seo_title' => 200, 'seo_description' => 300] as $key => $max) {
                $post[$key] = $this->value($key);
                if (mb_strlen($post[$key]) > $max) {
                    $errors[] = $key.' ยาวเกินกำหนด';
                }
            }
            if (! $post['title']) {
                $errors[] = 'กรุณากรอกชื่อบทความ';
            }
            if (! $post['slug']) {
                $post['slug'] = trim(mb_strtolower((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $post['title'])), '-');
            }
            if (! preg_match('/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/uD', $post['slug']) || mb_strlen($post['slug']) > 180) {
                $errors[] = 'URL ใช้ตัวอักษร ตัวเลข และขีดกลาง ความยาวไม่เกิน 180 ตัว';
            }
            if ($this->blog->slug_taken($post['slug'], $id)) {
                $errors[] = 'URL นี้มีบทความหรือลิงก์เก่าใช้งานแล้ว กรุณาเปลี่ยน URL';
            }
            $post['status'] = $this->value('status');
            if (! in_array($post['status'], ['draft', 'published'], true)) {
                $errors[] = 'สถานะไม่ถูกต้อง';
                $post['status'] = 'draft';
            }
            $post['category_id'] = $this->value('category_id') ?: null;
            if ($post['category_id'] && (! ctype_digit($post['category_id']) || ! DB::table('blog_categories')->where('id', $post['category_id'])->count())) {
                $errors[] = 'หมวดหมู่ไม่ถูกต้อง';
            }
            $post['content'] = $this->blog->clean_html($post['content']);
            if ($post['status'] === 'published' && ! trim(html_entity_decode(strip_tags($post['content']), ENT_QUOTES, 'UTF-8'))) {
                $errors[] = 'กรุณาเขียนเนื้อหาก่อนเผยแพร่';
            }
            $post['published_at'] = $original['published_at'];
            if ($post['status'] === 'published' && ! $post['published_at']) {
                $post['published_at'] = date('Y-m-d H:i:s');
            }
            if (! $errors && $this->value('updated_at') !== $original['updated_at']) {
                $errors[] = 'บทความถูกแก้ไขจากหน้าต่างอื่น กรุณาโหลดหน้าใหม่ก่อนบันทึก';
            }
            $upload = null;
            if (! $errors && isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $dir = base_path('public/uploads/blog/');
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 6000, 'max_height' => 6000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
                if (! $this->upload->do_upload('cover_file')) {
                    $errors[] = 'อัปโหลดไม่สำเร็จ: '.strip_tags($this->upload->display_errors());
                } else {
                    $f = $this->upload->data();
                    if (! @getimagesize($f['full_path'])) {
                        @unlink($f['full_path']);
                        $errors[] = 'กรุณาเลือกไฟล์ภาพ';
                    } else {
                        $upload = 'public/uploads/blog/'.$f['file_name'];
                    }
                }
            }
            if (! $errors) {
                $post['cover'] = $upload ?: ($this->value('remove_cover') === '1' ? '' : $original['cover']);
                $data = $post;
                unset($data['id']);
                $data['updated_at'] = date('Y-m-d H:i:s');
                $saved = $this->blog->save_post($id, $data, $original['updated_at']);
                if ($saved) {
                    session()->flash('success', 'บันทึกบทความเรียบร้อยแล้ว');
                    redirect_to(base_url().'admin/blog/'.$saved);
                }
                if ($upload) {
                    @unlink(base_path($upload));
                }$post['cover'] = $original['cover'];
                $errors[] = $this->blog->error;
            }
        }

        return $this->render('admin/blog_edit', ['title' => $id ? 'แก้ไขบทความ' : 'เขียนบทความใหม่', 'blog_page' => true, 'post' => $post, 'errors' => $errors, 'categories' => $this->blog->categories()], true);
    }

    public function preview($id)
    {
        $post = $this->blog->find((int) $id);
        if (! $post) {
            show_404();
        }$post['category_name'] = 'ตัวอย่างบทความ';
        set_header('X-Robots-Tag: noindex, nofollow');

        return $this->render('blog/show', ['title' => 'ตัวอย่าง: '.$post['title'], 'blog_page' => true, 'post' => $post, 'blog_preview' => true]);
    }

    public function state($id)
    {
        $this->post_only();
        $post = $this->blog->find((int) $id);
        if (! $post) {
            show_404();
        }$action = $this->value('action');
        if ($action === 'trash' && $post['status'] !== 'trash') {
            $status = 'trash';
        } elseif ($action === 'restore' && $post['status'] === 'trash') {
            $status = 'draft';
        } else {
            show_error('Invalid action', 400);
        }
        DB::table('blog_posts')->where('id', (int) $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        session()->flash('success', $status === 'trash' ? 'ย้ายไปถังขยะแล้ว สามารถกู้คืนได้' : 'กู้คืนเป็นฉบับร่างแล้ว');
        redirect_to(base_url().'admin/blog'.($status === 'trash' ? '?status=trash' : ''));
    }

    public function categories()
    {
        $error = '';
        $editing = null;
        $edit = request()->query('edit');
        if (is_string($edit) && ctype_digit($edit)) {
            $editing = DB::table('blog_categories')->where('id', (int) $edit)->first();
        }
        if (request_method() === 'post') {
            $id = (int) $this->value('id');
            $action = $this->value('action');
            if ($action === 'delete') {
                DB::table('blog_categories')->where('id', $id)->delete();
                redirect_to(base_url().'admin/blog/categories');
            }
            $name = $this->value('name');
            $slug = $this->value('slug');
            if (! $slug) {
                $slug = trim(mb_strtolower((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $name)), '-');
            }
            if (! $name || mb_strlen($name) > 100 || mb_strlen($slug) > 180 || ! preg_match('/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/uD', $slug)) {
                $error = 'กรอกชื่อไม่เกิน 100 ตัว และ URL ให้ถูกต้อง';
            } elseif (DB::table('blog_categories')->where('slug', $slug)->where('id', '!=', $id)->count()) {
                $error = 'URL หมวดหมู่นี้มีอยู่แล้ว';
            } else {
                // Legacy ignored a failed write here (duplicate-slug race, invalid bytes in the name) and redirected anyway.
                try {
                    if ($id) {
                        DB::table('blog_categories')->where('id', $id)->update(['name' => $name, 'slug' => $slug]);
                    } else {
                        DB::table('blog_categories')->insert(['name' => $name, 'slug' => $slug]);
                    }
                } catch (QueryException $e) {
                }
                redirect_to(base_url().'admin/blog/categories');
            }
            $editing = ['id' => $id, 'name' => $name, 'slug' => $slug];
        }

        return $this->render('admin/blog_categories',['title' => 'หมวดหมู่บทความ', 'blog_page' => true, 'categories' => $this->blog->categories(), 'error' => $error, 'editing' => $editing],true);
    }
}
