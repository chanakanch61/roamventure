<?php

namespace App\Providers;

use App\Repositories\AdminAccessRepository;
use App\Repositories\BillingRepository;
use App\Repositories\BlogRepository;
use App\Repositories\DestinationRepository;
use App\Repositories\GalleryRepository;
use App\Repositories\HomeDestinationRepository;
use App\Repositories\PackageLevelRepository;
use App\Repositories\PassportRepository;
use App\Repositories\TourSearchRepository;
use App\Repositories\TripRepository;
use App\Support\ResponseHeaders;
use App\Support\SiteGate;
use App\Support\Upload;
use App\Support\ViewState;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            TripRepository::class, BillingRepository::class, PassportRepository::class, DestinationRepository::class,
            PackageLevelRepository::class, AdminAccessRepository::class, HomeDestinationRepository::class,
            TourSearchRepository::class, BlogRepository::class, GalleryRepository::class,
            SiteGate::class, Upload::class, ResponseHeaders::class, ViewState::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        // Repositories and views work with rows as associative arrays.
        Event::listen(StatementPrepared::class, fn (StatementPrepared $event) => $event->statement->setFetchMode(PDO::FETCH_ASSOC));

        if ($this->app->isProduction()) {
            $this->logWarningsInsteadOfFailing();
        }
    }

    /**
     * Templates predate Laravel's rule that every PHP warning is fatal: a rarely used page reading an unset
     * variable should not take down a customer's booking. Local environments keep the strict behaviour,
     * so such reads get fixed; in production they are logged and the request carries on.
     */
    private function logWarningsInsteadOfFailing(): void
    {
        $previous = set_error_handler(function (int $level, string $message, string $file = '', int $line = 0) use (&$previous) {
            if ($level & (E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE)) {
                if (error_reporting() & $level) {
                    Log::warning($message, ['file' => $file, 'line' => $line]);
                }

                return true;
            }

            return $previous ? $previous($level, $message, $file, $line) : false;
        });
    }
}
