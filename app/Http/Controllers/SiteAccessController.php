<?php

namespace App\Http\Controllers;

class SiteAccessController extends Controller
{
    public function login()
    {
        $this->site_gate->no_cache();
        if (! $this->site_gate->enabled() || $this->site_gate->viewer() || $this->site_gate->staff()) {
            redirect_to($this->site_gate->destination());
        }
        $error = '';
        $username = '';
        if (request_method() === 'post') {
            $raw = request()->post('username');
            $username = is_string($raw) ? trim($raw) : '';
            $error = $this->site_gate->authenticate($raw, request()->post('password'));
            if (! $error) {
                redirect_to($this->site_gate->destination(), 303);
            }
        }

        return response(load_view('site_access/login', ['error' => $error, 'username' => $username], true));
    }

    public function logout()
    {
        $this->post_only();
        $this->site_gate->no_cache();
        $this->site_gate->logout();
        redirect_to(base_url().'site-login', 303);
    }
}
