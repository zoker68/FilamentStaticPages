<?php

use Zoker\FilamentStaticPages\Http\Controllers\PageController;

Route::fallback(PageController::class)->middleware('web')->prefix(config('filament-static-pages.route_prefix'))->name('filament-static-pages.page');
