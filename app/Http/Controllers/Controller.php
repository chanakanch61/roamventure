<?php

namespace App\Http\Controllers;

use App\Support\ResolvesServices;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

abstract class Controller
{
    use ResolvesServices;

    protected array $settings = [];

    /** Snake-case controller name, e.g. "home_destinations_admin", used for permissions and tracking. */
    protected string $controllerKey = '';

    /** The action handling the current request. */
    protected string $action = '';

    /**
     * Runs inside the middleware pipeline, unlike the constructor, so sessions and
     * the site gate are available before any action or boot() runs.
     */
    public function callAction($method, $parameters)
    {
        $this->action = $method;
        $this->controllerKey = Str::snake(Str::beforeLast(class_basename(static::class), 'Controller'));
        $this->settings = $this->trips->settings();
        $this->site_gate->configure($this->settings);
        $this->site_gate->enforce($this->controllerKey, $method);
        $this->boot();

        return $this->{$method}(...array_values($parameters));
    }

    /** Per-controller setup that must run before every action. */
    protected function boot(): void {}

    protected function render(string $view, array $data = [], bool $admin = false): Response
    {
        if ($view === 'home') {
            $data['destinationConfig'] = $this->home_places->config($this->settings);
            $data['homeCards'] = $this->home_places->cards($data['destinationConfig']);
        }
        if (! $admin && in_array($view, ['home', 'tours', 'destination'], true)) {
            $data['tour_search_catalog'] = $this->tour_search->catalog($view === 'destination' ? $data['node'] : null);
        }
        if (! $admin && in_array($view, ['home', 'tours'], true)) {
            $data['meta'] = seo_page_meta($this->settings, $view === 'home' ? 'home' : $data['listing_type']);
        }
        $data['settings'] = $this->settings;
        $data['admin_layout'] = $admin;
        $data['tracking'] = tracking_context($this->settings, $view, $data, $this->controllerKey);

        return response(load_view('layout/header', $data, true).load_view($view, $data, true).load_view('layout/footer', $data, true));
    }

    protected function require_admin(): void
    {
        if (! session('admin_id')) {
            redirect_to(base_url().'admin/login');
        }
        $actor = $this->access->current();
        if (! $actor) {
            session()->invalidate();
            redirect_to(base_url().'admin/login');
        }
        session()->put('admin_name', $actor['name']);
        $permission = $this->access->route_permission($this->controllerKey, $this->action);
        set_header('Cache-Control: no-store, private');
        if ($permission === false || ($permission !== null && ! $this->access->can($permission))) {
            show_error('คุณไม่มีสิทธิ์เข้าถึงส่วนนี้ กรุณาติดต่อผู้ดูแลระบบ', 403, 'ไม่มีสิทธิ์เข้าถึง');
        }
    }

    protected function post_only(): void
    {
        if (request_method() !== 'post') {
            show_error('Method not allowed', 405);
        }
    }
}
