<?php

namespace App\Http\Middleware;

use App\Support\ResponseHeaders;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyQueuedHeaders
{
    public function __construct(private ResponseHeaders $headers) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Pages carry a CSRF token and session state, so browsers must not keep them unless an action says otherwise.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');

        foreach ($this->headers->all() as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
