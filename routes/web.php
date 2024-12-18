<?php

use Illuminate\Support\Facades\Route;
use Zoker\FilamentStaticPages\Http\Controllers\PageController;
use Zoker\FilamentStaticPages\Models\Page;

//Route::fallback(PageController::class)->middleware('web')->prefix(config('filament-static-pages.route_prefix'))->name('filament-static-pages.page');
Route::middleware('web')->prefix(config('filament-static-pages.route_prefix'))->name('fsp.')->group(function () {
    foreach (Page::getAllRoutes() as $route) {
        Route::get($route, PageController::class)->name($route);
    }
});
