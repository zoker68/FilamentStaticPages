<?php

use Illuminate\Support\Facades\Route;
use Zoker\FilamentStaticPages\Http\Controllers\PageController;
use Zoker\FilamentStaticPages\Models\Page;

Route::middleware(config('filament-static-pages.middlewares'))
    ->prefix(config('filament-static-pages.route_prefix'))
    ->name('fsp.')
    ->group(function () {
        foreach (Page::getAllRoutes() as $route) {
            Route::get($route ?? '/', PageController::class)->name($route);
        }
    });
