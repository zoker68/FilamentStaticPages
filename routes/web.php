<?php

use Illuminate\Support\Facades\Route;
use Zoker\FilamentStaticPages\Http\Controllers\PageController;
use Zoker\FilamentStaticPages\Models\Page;

Route::middleware(['web'])->group(function () {
    $routes = Page::getAllRoutes();
    Route::multisite(function () use ($routes) {
        foreach ($routes as $route) {

            Route::middleware(config('filament-static-pages.middlewares'))
                ->domain($route['site']['domain'])
                ->prefix(config('filament-static-pages.route_prefix'))
                ->name('fsp.' . $route['url'])
                ->get($route['url'] ?? '/', PageController::class);
        }
    });
});
