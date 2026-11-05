<?php

namespace App\Support;

use Symfony\Component\HttpKernel\Exception\HttpException;

/** An error page with its own heading, raised through show_error(). */
class ShownError extends HttpException
{
    public function __construct(string $message, int $status, public readonly string $heading)
    {
        parent::__construct($status, $message);
    }
}
