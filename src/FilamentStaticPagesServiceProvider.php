<?php

namespace Zoker\FilamentStaticPages;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentStaticPagesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-static-pages')
            ->hasViews()
            ->hasMigration(
                '0001_01_01_000001_create_zoker_pages_pages_table',
            )
            ->runsMigrations();

    }
}