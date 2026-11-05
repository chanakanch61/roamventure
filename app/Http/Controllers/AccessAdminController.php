<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AccessAdminController extends Controller
{
    /** True once a statement of the open transaction has failed. */
    private bool $transFailed = false;

    protected function boot(): void
    {
        $this->require_admin();
    }

    private function value($key)
    {
        $v = request()->post($key);

        return is_string($v) ? trim($v) : '';
    }

    private function lock_access()
    {
        DB::beginTransaction();
        $this->transFailed = false;
        try {
            DB::select('SELECT id FROM admin_roles ORDER BY id FOR UPDATE');
        } catch (QueryException $e) {
            $this->transFailed = true;
        }
        try {
            DB::select('SELECT id FROM admins ORDER BY id FOR UPDATE');
        } catch (QueryException $e) {
            $this->transFailed = true;
        }
        // Recheck after acquiring locks: permissions may have changed while waiting.
        $actor = $this->access->refresh();
        $permission = $this->access->route_permission($this->controllerKey, $this->action);
        if (! $actor || ! $permission || ! $this->access->can($permission)) {
            DB::rollBack();
            show_error('สิทธิ์ของบัญชีมีการเปลี่ยนแปลง กรุณาเข้าสู่ระบบใหม่', 403);
        }
    }

    private function complete($errors, $url)
    {
        if ($errors || $this->transFailed) {
            DB::rollBack();

            return $errors ?: ['บันทึกไม่สำเร็จ กรุณาลองใหม่'];
        }
        DB::commit();
        session()->flash('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
        redirect_to(base_url().$url);
    }

    public function index()
    {
        $q = request()->query('q');
        $q = is_string($q) ? mb_substr(trim($q), 0, 100) : '';
        $query = DB::table('admins as a')->select('a.id', 'a.name', 'a.email', 'a.is_active', 'a.last_login_at', 'r.name as role_name')->leftJoin('admin_roles as r', 'r.id', '=', 'a.role_id');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where('a.name', 'like', $like)->orWhere('a.email', 'like', $like);
            });
        }

        return $this->render('admin/access_admins', ['title' => 'จัดการแอดมิน', 'accounts' => $query->orderBy('a.id', 'DESC')->get()->all(), 'q' => $q], true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $account = $id ? DB::table('admins')->where('id', $id)->first() : ['name' => '', 'email' => '', 'role_id' => '', 'is_active' => 1];
        if (! $account) {
            show_404();
        } $errors = [];
        $actor = $this->access->current();
        if (request_method() === 'post') {
            $this->lock_access();
            $existing = $id ? DB::table('admins')->where('id', $id)->first() : null;
            $account = ['name' => $this->value('name'), 'email' => strtolower($this->value('email')), 'role_id' => (int) $this->value('role_id'), 'is_active' => $this->value('is_active') === '1' ? 1 : 0];
            $role = DB::table('admin_roles')->where('id', $account['role_id'])->first();
            if (! $this->access->assignable($role)) {
                $errors[] = 'คุณไม่สามารถมอบหมายบทบาทที่มีสิทธิ์สูงกว่าตนเองได้';
            }
            if ($id && ! $existing) {
                $errors[] = 'ไม่พบบัญชีนี้';
            }
            if ($existing) {
                $oldRole = DB::table('admin_roles')->where('id', $existing['role_id'])->first();
                if ($oldRole && ! $this->access->assignable($oldRole)) {
                    $errors[] = 'คุณไม่มีสิทธิ์แก้ไขบัญชีนี้';
                }
                if ($id === (int) $actor['id'] && (! $account['is_active'] || $account['role_id'] !== (int) $existing['role_id'])) {
                    $errors[] = 'ไม่สามารถปิดใช้งานหรือเปลี่ยนบทบาทของตนเองได้';
                }
                if ($oldRole && $oldRole['is_super'] && $existing['is_active'] && (! $account['is_active'] || ! $role || ! $role['is_super'])) {
                    $owners = DB::table('admins as a')->join('admin_roles as r', 'r.id', '=', 'a.role_id')->where('a.is_active', 1)->where('r.is_super', 1)->count();
                    if ($owners <= 1) {
                        $errors[] = 'ต้องมี Super Admin ที่เปิดใช้งานอย่างน้อยหนึ่งบัญชี';
                    }
                }
            }
            if ($account['name'] === '' || mb_strlen($account['name']) > 100) {
                $errors[] = 'กรุณาระบุชื่อไม่เกิน 100 ตัวอักษร';
            }
            if (strlen($account['email']) > 190 || ! filter_var($account['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'กรุณาระบุอีเมลให้ถูกต้อง';
            }
            if (DB::table('admins')->where('email', $account['email'])->where('id', '!=', $id)->count()) {
                $errors[] = 'อีเมลนี้มีบัญชีอยู่แล้ว';
            }
            $password = request()->post('password');
            if (! is_string($password) || (! $id && $password === '') || ($password !== '' && (strlen($password) < 12 || strlen($password) > 72))) {
                $errors[] = 'รหัสผ่านต้องมีความยาว 12–72 ไบต์';
            }
            if (! $errors) {
                $data = $account;
                if ($password !== '') {
                    $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                try {
                    if ($id) {
                        DB::table('admins')->where('id', $id)->update($data);
                    } else {
                        DB::table('admins')->insert($data);
                    }
                } catch (QueryException $e) {
                    $this->transFailed = true;
                }
                if ($id === (int) $actor['id'] && $password !== '') {
                    session()->regenerate(true);
                    session()->put('admin_auth', hash('sha256', $data['password_hash']));
                }
            }
            $errors = $this->complete($errors, 'admin/admins');
        }
        $roles = array_filter(DB::table('admin_roles')->orderBy('is_super', 'DESC')->orderBy('name')->get()->all(), function ($r) {
            return $this->access->assignable($r);
        });

        return $this->render('admin/access_admin_form', ['title' => $id ? 'แก้ไขแอดมิน' : 'เพิ่มแอดมิน', 'account' => $account, 'id' => $id, 'roles' => $roles, 'errors' => $errors], true);
    }

    public function roles()
    {
        $roles = DB::select('SELECT r.*, (SELECT COUNT(*) FROM admins a WHERE a.role_id=r.id) members, (SELECT COUNT(*) FROM admin_role_permissions p WHERE p.role_id=r.id) permission_count FROM admin_roles r ORDER BY r.is_super DESC,r.name');

        return $this->render('admin/access_roles', ['title' => 'บทบาทและสิทธิ์', 'roles' => $roles], true);
    }

    public function role($id = 0)
    {
        $id = (int) $id;
        $role = $id ? DB::table('admin_roles')->where('id', $id)->first() : ['name' => '', 'description' => '', 'is_super' => 0];
        if (! $role) {
            show_404();
        } $errors = [];
        $selected = $id ? $this->access->permissions($id) : [];
        if (request_method() === 'post') {
            $this->lock_access();
            $existing = $id ? DB::table('admin_roles')->where('id', $id)->first() : null;
            if ($id && (! $existing || $existing['is_super'] || ! $this->access->assignable($existing))) {
                $errors[] = 'ไม่สามารถแก้ไขบทบาทนี้ได้';
            }
            $actor = $this->access->current();
            if (! $actor['is_super'] && $id === (int) $actor['role_id']) {
                $errors[] = 'ไม่สามารถแก้ไขสิทธิ์บทบาทของตนเองได้';
            }
            $role['name'] = $this->value('name');
            $role['description'] = $this->value('description');
            $posted = request()->post('permissions');
            $selected = [];
            if ($posted !== null && ! is_array($posted)) {
                $errors[] = 'รูปแบบสิทธิ์ไม่ถูกต้อง';
            } else {
                foreach ($posted ?: [] as $p) {
                    if (! is_string($p) || ! $this->access->can($p)) {
                        $errors[] = 'ไม่สามารถมอบสิทธิ์ที่ตนเองไม่มีได้';

                        continue;
                    }
                    $selected[] = $p;
                }
            }
            $selected = array_unique($selected);
            if ($this->value('form_complete') !== '1') {
                $errors[] = 'ข้อมูลแบบฟอร์มไม่ครบ กรุณาลองใหม่';
            }
            if ($role['name'] === '' || mb_strlen($role['name']) > 100 || mb_strlen($role['description']) > 500) {
                $errors[] = 'กรุณาระบุชื่อไม่เกิน 100 และคำอธิบายไม่เกิน 500 ตัวอักษร';
            }
            if (DB::table('admin_roles')->where('name', $role['name'])->where('id', '!=', $id)->count()) {
                $errors[] = 'ชื่อบทบาทนี้มีอยู่แล้ว';
            }
            if (! $errors) {
                $data = ['name' => $role['name'], 'description' => $role['description']];
                try {
                    if ($id) {
                        DB::table('admin_roles')->where('id', $id)->update($data);
                    } else {
                        $id = DB::table('admin_roles')->insertGetId($data);
                    }
                    DB::table('admin_role_permissions')->where('role_id', $id)->delete();
                    foreach ($selected as $p) {
                        DB::table('admin_role_permissions')->insert(['role_id' => $id, 'permission' => $p]);
                    }
                } catch (QueryException $e) {
                    $this->transFailed = true;
                }
            }
            $errors = $this->complete($errors, 'admin/roles');
        }

        return $this->render('admin/access_role_form', ['title' => $id ? 'แก้ไขบทบาท' : 'เพิ่มบทบาท', 'role' => $role, 'selected' => $selected, 'catalog' => $this->access->catalog(), 'errors' => $errors], true);
    }

    public function delete_role($id)
    {
        $this->post_only();
        $this->lock_access();
        $role = DB::table('admin_roles')->where('id', (int) $id)->first();
        if (! $role || $role['is_super'] || ! $this->access->assignable($role) || DB::table('admins')->where('role_id', (int) $id)->count()) {
            DB::rollBack();
            session()->flash('error', 'ลบไม่ได้: บทบาทถูกใช้งาน เป็น Super Admin หรือคุณไม่มีสิทธิ์');
        } else {
            // Legacy ignored a failed delete here: it committed and reported success.
            try {
                DB::table('admin_roles')->where('id', (int) $id)->delete();
            } catch (QueryException $e) {
            }
            DB::commit();
            session()->flash('success','ลบบทบาทแล้ว');
        }
        redirect_to(base_url().'admin/roles');
    }
}
