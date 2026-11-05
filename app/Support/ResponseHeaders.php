<?php

namespace App\Support;

/**
 * Headers queued while a request is handled, applied to whatever response ends it,
 * including redirects and error pages raised part way through an action.
 */
class ResponseHeaders
{
    /** @var array<string, string> */
    private array $headers = [];

    public function set(string $header): void
    {
        [$name, $value] = explode(':', $header, 2);
        $this->headers[trim($name)] = trim($value);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->headers;
    }
}
