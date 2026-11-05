<?php

use App\Http\Controllers\AccessAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AirlinesAdminController;
use App\Http\Controllers\VehicleTemplatesAdminController;
use App\Http\Controllers\BannersAdminController;
use App\Http\Controllers\BillingAdminController;
use App\Http\Controllers\BlogAdminController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DestinationsAdminController;
use App\Http\Controllers\GalleryAdminController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomeDestinationsAdminController;
use App\Http\Controllers\PackageLevelsAdminController;
use App\Http\Controllers\PassportsController;
use App\Http\Controllers\SeoAdminController;
use App\Http\Controllers\SiteAccessController;
use Illuminate\Support\Facades\Route;

// Actions branch on the request method themselves, so routes accept both GET and POST.
// Order matters: the first matching pattern wins.
$page = fn (string $uri, array $action) => Route::match(['GET', 'HEAD', 'POST'], $uri, $action);

$page('/', [HomeController::class, 'index']);

$page('site-login', [SiteAccessController::class, 'login']);
$page('site-logout', [SiteAccessController::class, 'logout']);

// Administration
$page('admin', [AdminController::class, 'index']);
$page('admin/login', [AdminController::class, 'login']);
$page('admin/logout', [AdminController::class, 'logout']);
$page('admin/site-access', [AdminController::class, 'site_access']);
$page('admin/settings', [AdminController::class, 'settings']);
$page('admin/setting', [AdminController::class, 'settings']);

$page('admin/admins', [AccessAdminController::class, 'index']);
$page('admin/admins/new', [AccessAdminController::class, 'edit']);
$page('admin/admins/{id}', [AccessAdminController::class, 'edit'])->whereNumber('id');
$page('admin/roles', [AccessAdminController::class, 'roles']);
$page('admin/roles/new', [AccessAdminController::class, 'role']);
$page('admin/roles/{id}/delete', [AccessAdminController::class, 'delete_role'])->whereNumber('id');
$page('admin/roles/{id}', [AccessAdminController::class, 'role'])->whereNumber('id');

$page('admin/home-destinations', [HomeDestinationsAdminController::class, 'index']);
$page('admin/banners', [BannersAdminController::class, 'index']);
$page('admin/seo', [SeoAdminController::class, 'index']);
$page('admin/seo/{key}', [SeoAdminController::class, 'edit']);

$page('admin/destinations', [DestinationsAdminController::class, 'index']);
$page('admin/destinations/upload-image', [DestinationsAdminController::class, 'upload_image']);
$page('admin/destinations/clean-content', [DestinationsAdminController::class, 'clean_content']);
$page('admin/destinations/{kind}/new', [DestinationsAdminController::class, 'edit']);
$page('admin/destinations/{kind}/{id}/source-pdf', [DestinationsAdminController::class, 'source_pdf'])->whereNumber('id');
$page('admin/destinations/{kind}/{id}/delete', [DestinationsAdminController::class, 'delete'])->whereNumber('id');
$page('admin/destinations/{kind}/{id}', [DestinationsAdminController::class, 'edit'])->whereNumber('id');
$page('admin/destinations/{kind}', [DestinationsAdminController::class, 'index']);

$page('admin/payment-settings', [BillingAdminController::class, 'settings']);
$page('admin/payments/{id}', [BillingAdminController::class, 'review'])->whereNumber('id');
$page('admin/bookings/{id}/extend', [BillingAdminController::class, 'extend'])->whereNumber('id');
$page('admin/bookings/{bookingId}/passport/{number}', [PassportsController::class, 'admin'])->whereNumber(['bookingId', 'number']);

$page('admin/package-levels', [PackageLevelsAdminController::class, 'index']);
$page('admin/package-levels/new', [PackageLevelsAdminController::class, 'edit']);
$page('admin/package-levels/import', [PackageLevelsAdminController::class, 'import']);
$page('admin/package-levels/{id}/delete', [PackageLevelsAdminController::class, 'delete'])->whereNumber('id');
$page('admin/package-levels/{id}', [PackageLevelsAdminController::class, 'edit'])->whereNumber('id');

$page('admin/airlines', [AirlinesAdminController::class, 'index']);
$page('admin/airlines/new', [AirlinesAdminController::class, 'edit']);
$page('admin/airlines/{id}/delete', [AirlinesAdminController::class, 'delete'])->whereNumber('id');
$page('admin/airlines/{id}', [AirlinesAdminController::class, 'edit'])->whereNumber('id');
$page('admin/vehicle-templates', [VehicleTemplatesAdminController::class, 'index']);
$page('admin/vehicle-templates/new', [VehicleTemplatesAdminController::class, 'edit']);
$page('admin/vehicle-templates/photo', [VehicleTemplatesAdminController::class, 'photo']);
$page('admin/vehicle-templates/from-program', [VehicleTemplatesAdminController::class, 'from_program']);
$page('admin/vehicle-templates/{id}/delete', [VehicleTemplatesAdminController::class, 'delete'])->whereNumber('id');
$page('admin/vehicle-templates/{id}', [VehicleTemplatesAdminController::class, 'edit'])->whereNumber('id');

$page('admin/tours', [AdminController::class, 'tours']);
$page('admin/tours/add', [AdminController::class, 'edit']);
$page('admin/tours/new', [AdminController::class, 'edit']);
$page('admin/tours/hotel-photo', [AdminController::class, 'hotel_photo']);
$page('admin/tours/{id}', [AdminController::class, 'edit'])->whereNumber('id');
$page('admin/tours/{id}/source-pdf', [AdminController::class, 'source_pdf'])->whereNumber('id');
$page('admin/tours/{id}/delete', [AdminController::class, 'delete'])->whereNumber('id');
$page('admin/preview/{id}', [AdminController::class, 'preview'])->whereNumber('id');
$page('admin/bookings', [AdminController::class, 'bookings']);
$page('admin/bookings/new', [AdminController::class, 'new_booking']);
$page('admin/bookings/{id}', [AdminController::class, 'booking'])->whereNumber('id');

$page('admin/blog', [BlogAdminController::class, 'index']);
$page('admin/blog/new', [BlogAdminController::class, 'edit']);
$page('admin/blog/upload-image', [BlogAdminController::class, 'upload_image']);
$page('admin/blog/clean-content', [BlogAdminController::class, 'clean_content']);
$page('admin/blog/categories', [BlogAdminController::class, 'categories']);
$page('admin/blog/{id}/preview', [BlogAdminController::class, 'preview'])->whereNumber('id');
$page('admin/blog/{id}/state', [BlogAdminController::class, 'state'])->whereNumber('id');
$page('admin/blog/{id}', [BlogAdminController::class, 'edit'])->whereNumber('id');

$page('admin/gallery', [GalleryAdminController::class, 'index']);
$page('admin/gallery/new', [GalleryAdminController::class, 'edit']);
$page('admin/gallery/{id}/delete', [GalleryAdminController::class, 'delete'])->whereNumber('id');
$page('admin/gallery/{id}', [GalleryAdminController::class, 'edit'])->whereNumber('id');

// Public site
$page('tours', [HomeController::class, 'tours']);
$page('tours-with-flight', [HomeController::class, 'flight_tours']);
$page('land-tours', [HomeController::class, 'land_tours']);
$page('packages', [HomeController::class, 'packages']);
$page('aboutus', [CompanyController::class, 'about']);
$page('contactus', [CompanyController::class, 'contact']);
$page('about', [CompanyController::class, 'legacy_about']);
$page('about-us', [CompanyController::class, 'legacy_about']);
$page('contact', [CompanyController::class, 'legacy_contact']);
$page('contact-us', [CompanyController::class, 'legacy_contact']);
$page('sitemap.xml', [CompanyController::class, 'sitemap']);
$page('robots.txt', [CompanyController::class, 'robots']);
$page('privacy', [HomeController::class, 'privacy']);

$page('destinations/{kind}/{slug}', [HomeController::class, 'destination']);
$page('tours/{slug}', [HomeController::class, 'tour_slug']);
$page('tour/{country}/{route}', [HomeController::class, 'route_destination']);

$page('booking', [HomeController::class, 'book']);
$page('booking/success', [HomeController::class, 'success']);
$page('booking/detail/{token}', [BookingController::class, 'detail']);
$page('booking/passport/{token}/{number}', [PassportsController::class, 'customer'])->whereNumber('number');
$page('booking/passport/{token}', [PassportsController::class, 'upload']);
$page('booking/payment/{token}', [BookingController::class, 'payment']);
$page('booking/invoice/{token}', [BookingController::class, 'document']);
$page('booking/receipt/{token}/{paymentId}', [BookingController::class, 'document'])->whereNumber('paymentId');
$page('booking/slip/{token}/{paymentId}', [BookingController::class, 'slip'])->whereNumber('paymentId');

$page('blog', [BlogController::class, 'index']);
$page('blog/{slug}', [BlogController::class, 'show']);

$page('gallery', [GalleryController::class, 'index']);
$page('gallery/{id}', [GalleryController::class, 'show'])->whereNumber('id');
