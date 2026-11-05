<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Repairs invalid UTF-8 and strips control characters from query and POST values,
 * so that malformed input cannot reach a utf8mb4 column in strict mode and fail the query.
 */
class CleanInput
{
    public function handle(Request $request, Closure $next): Response
    {
        // Invalid bytes are dropped, not replaced with "?".
        mb_substitute_character('none');
        $request->query->replace($this->clean($request->query->all()));
        $request->request->replace($this->clean($request->request->all()));

        return $next($request);
    }

    private function clean(array $values): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->clean($value);
            }
            if (! is_string($value)) {
                return $value;
            }

            return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', mb_convert_encoding($value, 'UTF-8', 'UTF-8'));
        }, $values);
    }
}
