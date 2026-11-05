<?php

namespace App\Support;

use App\Repositories\AdminAccessRepository;
use App\Repositories\AirlineRepository;
use App\Repositories\BillingRepository;
use App\Repositories\BlogRepository;
use App\Repositories\DestinationRepository;
use App\Repositories\GalleryRepository;
use App\Repositories\HomeDestinationRepository;
use App\Repositories\PackageLevelRepository;
use App\Repositories\PassportRepository;
use App\Repositories\TourSearchRepository;
use App\Repositories\TripRepository;
use App\Repositories\VehicleTemplateRepository;
use LogicException;

/** Short property access ($this->trips, $this->billing, ...) to the shared per-request services. */
trait ResolvesServices
{
    public function __get(string $name)
    {
        $class = [
            'trips' => TripRepository::class,
            'billing' => BillingRepository::class,
            'passports' => PassportRepository::class,
            'places' => DestinationRepository::class,
            'package_levels' => PackageLevelRepository::class,
            'airlines' => AirlineRepository::class,
            'vehicle_templates' => VehicleTemplateRepository::class,
            'access' => AdminAccessRepository::class,
            'home_places' => HomeDestinationRepository::class,
            'tour_search' => TourSearchRepository::class,
            'blog' => BlogRepository::class,
            'destination_html' => BlogRepository::class,
            'gallery' => GalleryRepository::class,
            'site_gate' => SiteGate::class,
            'upload' => Upload::class,
        ][$name] ?? throw new LogicException("Unknown service [{$name}]");

        return app($class);
    }
}
