<?php

use App\Http\Middleware\ApplyQueuedHeaders;
use App\Http\Middleware\CleanInput;
use App\Support\ShownError;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Session\TokenMismatchException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Controllers validate raw POST values themselves; empty strings and spacing are significant.
        $middleware->remove([TrimStrings::class, ConvertEmptyStringsToNull::class]);
        // Views use their own $errors arrays.
        $middleware->web(remove: [ShareErrorsFromSession::class]);
        $middleware->append(CleanInput::class);
        $middleware->append(ApplyQueuedHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e) {
            $status = $e->getStatusCode();
            if ($e->getPrevious() instanceof TokenMismatchException) {
                [$status, $heading, $message] = [403, 'An Error Was Encountered', 'The action you have requested is not allowed.'];
            } else {
                $heading = $e instanceof ShownError ? $e->heading : ($status === 404 ? '404 Page Not Found' : 'An Error Was Encountered');
                $message = $e instanceof ShownError ? $e->getMessage() : ($status === 404 ? 'The page you requested was not found.' : 'The request could not be completed.');
            }

            return response()->view('errors/general', ['heading' => $heading, 'message' => $message], $status, $e->getHeaders());
        });
    })->create();
