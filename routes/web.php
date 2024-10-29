<?php

use Zoker\FilamentStaticPages\Http\Controllers\PageController;

Route::prefix(config('filament-static-pages.route_prefix'))->get('/{page:url}', PageController::class)->name('filament-static-pages.page');
