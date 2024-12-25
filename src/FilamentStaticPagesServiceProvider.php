<?php

namespace Zoker\FilamentStaticPages;

use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentStaticPagesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-static-pages')
            ->hasViews()
            ->hasConfigFile()
            ->hasViews('fsp')
            ->hasMigrations([
                'create_zoker_pages_pages_table',
                'create_zoker_pages_blocks_table',
            ]);

        Blade::componentNamespace('Zoker\\FilamentStaticPages\\View\\Components', 'fsp');
    }

    public function bootingPackage()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
