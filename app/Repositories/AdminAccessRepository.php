<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Support\Facades\DB;

class AdminAccessRepository
{
    use ResolvesServices;

    private $identity;

    private $loaded = false;

    public function refresh()
    {
        $this->loaded = false;

        return $this->current();
    }

    public function catalog()
    {
        return config('admin_permissions');
    }

    public function current()
    {
        if (! $this->loaded) {
            $this->loaded = true;
            $this->identity = DB::table('admins as a')->selectRaw('a.*,r.name role_name,r.is_super')->join('admin_roles as r', 'r.id', '=', 'a.role_id')->where('a.id', (int) session('admin_id'))->where('a.is_active', 1)->first();
            if ($this->identity) {
                // A password reset invalidates existing sessions on their next request.
                if (! hash_equals(hash('sha256', $this->identity['password_hash']), (string) session('admin_auth'))) {
                    $this->identity = null;
                } else {
                    $this->identity['permissions'] = array_column(DB::table('admin_role_permissions')->where('role_id', $this->identity['role_id'])->get()->all(), 'permission');
                }
            }
        }

        return $this->identity;
    }

    public function can($permission)
    {
        $a = $this->current();

        return $a && isset($this->catalog()[$permission]) && ($a['is_super'] || in_array($permission, $a['permissions'], true));
    }

    public function permissions($id)
    {
        return array_column(DB::table('admin_role_permissions')->where('role_id', $id)->get()->all(), 'permission');
    }

    public function assignable($role)
    {
        $a = $this->current();
        if (! $a || ! $role) {
            return false;
        }
        if ($a['is_super']) {
            return true;
        }

        return ! $role['is_super'] && ! array_diff($this->permissions($role['id']), $a['permissions']);
    }

    public function route_permission($controller, $method)
    {
        $controller = strtolower($controller);
        $method = strtolower($method);
        $map = ['gallery_admin' => 'gallery.manage', 'blog_admin' => 'blog.manage', 'package_levels_admin' => 'packages.manage', 'airlines_admin' => 'tours.manage', 'destinations_admin' => 'destinations.manage', 'home_destinations_admin' => 'home.manage', 'banners_admin' => 'banners.manage', 'seo_admin' => 'seo.manage', 'passports' => 'bookings.manage'];
        if (isset($map[$controller])) {
            return $map[$controller];
        }
        if ($controller === 'billing_admin') {
            return $method === 'settings' ? 'payments.manage' : (in_array($method, ['review', 'extend'], true) ? 'bookings.manage' : false);
        }
        if ($controller === 'access_admin') {
            return in_array($method, ['roles', 'role', 'delete_role'], true) ? 'roles.manage' : (in_array($method, ['index', 'edit'], true) ? 'admins.manage' : false);
        }
        if ($controller === 'admin') {
            if (in_array($method, ['index', 'logout'], true)) {
                return null;
            }
            if (in_array($method, ['tours', 'edit', 'delete', 'preview', 'source_pdf', 'hotel_photo'], true)) {
                return 'tours.manage';
            }
            if (in_array($method, ['bookings', 'booking', 'new_booking'], true)) {
                return 'bookings.manage';
            }
            if (in_array($method, ['settings', 'site_access'], true)) {
                return 'settings.manage';
            }
        }

        return false; // Unmapped protected actions deny by default.
    }

    public function menu()
    {
        return ['admin' => ['◫', 'ภาพรวม', null], 'admin/tours' => ['▧', 'โปรแกรมทัวร์', 'tours.manage'], 'admin/gallery' => ['&#9639;', 'Gallery', 'gallery.manage'], 'admin/blog' => ['✎', 'Blog', 'blog.manage'], 'admin/package-levels' => ['◇', 'ระดับบริการ', 'packages.manage'], 'admin/airlines' => ['✈', 'สายการบิน', 'tours.manage'], 'admin/destinations' => ['⌖', 'Zone / Country / Route', 'destinations.manage'], 'admin/bookings' => ['▤', 'คำขอจอง', 'bookings.manage'], 'admin/home-destinations' => ['⌖', 'จุดหมายแนะนำหน้าแรก', 'home.manage'], 'admin/banners' => ['▧', 'แบนเนอร์เว็บไซต์', 'banners.manage'], 'admin/seo' => ['⌕', 'Meta Tag หน้าเว็บ', 'seo.manage'], 'admin/settings' => ['⚙', 'ตั้งค่าเว็บไซต์', 'settings.manage'], 'admin/site-access' => ['⚿', 'จำกัดการเข้าชม', 'settings.manage'], 'admin/payment-settings' => ['฿', 'ช่องทางรับชำระ', 'payments.manage'], 'admin/admins' => ['♙', 'จัดการแอดมิน', 'admins.manage'], 'admin/roles' => ['⚿', 'บทบาทและสิทธิ์', 'roles.manage']];
    }
}
