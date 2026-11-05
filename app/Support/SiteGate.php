<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Temporary front-office access, independent of staff permissions.
class SiteGate
{
    use ResolvesServices;

    private array $settings = [];

    public function configure(array $settings): void
    {
        $this->settings = $settings;
    }

    public function enabled(): bool
    {
        return ($this->settings['site_access_enabled'] ?? '1') !== '0';
    }

    public function configured(): bool
    {
        return ! empty($this->settings['site_access_password_hash']);
    }

    private function fingerprint(): string
    {
        return hash('sha256', ($this->settings['site_access_username'] ?? '')."\0".($this->settings['site_access_password_hash'] ?? '')."\0".($this->settings['site_access_revision'] ?? ''));
    }

    public function viewer(): bool
    {
        $auth = session('site_viewer_auth');

        return $this->configured() && is_string($auth) && hash_equals($this->fingerprint(), $auth) && (int) session('site_viewer_expires') > time();
    }

    public function staff(): bool
    {
        if (! session('admin_id')) {
            return false;
        }

        return (bool) $this->access->current();
    }

    public function no_cache(): void
    {
        set_header('Cache-Control: no-store, private, max-age=0');
        set_header('X-Robots-Tag: noindex, nofollow, noarchive');
    }

    public function enforce(string $class, string $method): void
    {
        if (! $this->enabled()) {
            return;
        }
        $this->no_cache();
        // Exempt resolved controllers, never a URL prefix that can be forged.
        if (in_array($class, ['admin', 'access_admin', 'banners_admin', 'billing_admin', 'blog_admin', 'destinations_admin', 'gallery_admin', 'home_destinations_admin', 'package_levels_admin', 'seo_admin', 'site_access'], true) || ($class === 'passports' && $method === 'admin')) {
            return;
        }
        if ($this->viewer() || $this->staff()) {
            return;
        }
        if (request_method() === 'get') {
            $query = request()->server('QUERY_STRING', '');
            session()->put('site_access_return', uri_string().($query !== '' ? '?'.$query : ''));
        }
        redirect_to(base_url().'site-login', 303);
    }

    public function authenticate($username, $password): string
    {
        $ip = 'site:'.request()->ip();
        $since = date('Y-m-d H:i:s', time() - 900);
        $attempts = DB::table('login_attempts')->where('ip', $ip)->where('attempted_at', '>=', $since)->count();
        if ($attempts >= 8) {
            return 'ลองเข้าสู่ระบบหลายครั้งเกินไป กรุณารอ 15 นาที';
        }
        $valid = is_string($username) && is_string($password) && strlen($password) <= 72 && $this->configured();
        if ($valid) {
            $passwordMatches = password_verify($password, $this->settings['site_access_password_hash']);
            $valid = hash_equals($this->settings['site_access_username'] ?? '', trim($username)) && $passwordMatches;
        }
        if (! $valid) {
            DB::table('login_attempts')->insert(['ip' => $ip, 'attempted_at' => date('Y-m-d H:i:s')]);

            return 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
        session()->regenerate(true);
        session()->put(['site_viewer_auth' => $this->fingerprint(), 'site_viewer_expires' => time() + 7200]);
        DB::table('login_attempts')->where('ip', $ip)->delete();

        return '';
    }

    public function destination(): string
    {
        $path = session()->pull('site_access_return');
        // Always return to a path beneath the configured application URL.
        if (! is_string($path) || preg_match('/[\x00-\x20\x7f\\\\]/', $path) || preg_match('~^(?:/|[a-z][a-z0-9+.-]*:|site-login|site-logout|site_access)~i', $path) || preg_match('~(?:^|/)\.\.(?:/|$)~', rawurldecode(explode('?', $path, 2)[0]))) {
            $path = '';
        }

        return base_url().$path;
    }

    public function logout(): void
    {
        session()->forget(['site_viewer_auth', 'site_viewer_expires', 'site_access_return']);
    }
}
