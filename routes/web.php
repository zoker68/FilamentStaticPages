<?php

use Illuminate\Support\Facades\Route;
use Zoker\FilamentStaticPages\Http\Controllers\PageController;
use Zoker\FilamentStaticPages\Models\Page;

Route::middleware(['web'])->group(function () {
    $allowedUrls = Page::getAllowedUrls();
    //    dd($allowedUrls);
    Route::multisite(function () use ($allowedUrls) {
        if (in_array(null, $allowedUrls)) {
            Route::get('/', PageController::class)->name('index');
        }

        Route::middleware(config('filament-static-pages.middlewares'))
            ->prefix(config('filament-static-pages.route_prefix'))
            ->name('fsp.')
            ->group(function () use ($allowedUrls) {
                Route::get('{page}', PageController::class)
                    ->name('page')
                    ->whereIn('page', $allowedUrls);
            });
    });
});
