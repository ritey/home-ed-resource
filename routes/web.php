<?php

use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\HomeController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
| Public, read-only pages. They take no input, so they skip the session,
| cookie and CSRF middleware: no Set-Cookie on any response, which keeps the
| site cookie-free (no banner needed) and lets a CDN cache it.
*/
Route::withoutMiddleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/llms.txt', [DiscoveryController::class, 'llms'])->name('llms');
    Route::get('/sitemap.xml', [DiscoveryController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [DiscoveryController::class, 'robots'])->name('robots');
});
