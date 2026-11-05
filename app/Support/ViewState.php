<?php

namespace App\Support;

/**
 * Variables accumulated across load_view() calls in one request. Partials rely on seeing
 * everything passed to earlier views without it being handed down explicitly.
 */
class ViewState
{
    /** @var array<string, mixed> */
    private array $vars = [];

    /**
     * @param  array<string, mixed>  $vars
     * @return array<string, mixed>
     */
    public function merge(array $vars): array
    {
        return $this->vars = array_merge($this->vars, $vars);
    }
}
