<?php

namespace Zoker\FilamentStaticPages;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Zoker\FilamentStaticPages\Components\ContentBlock;
use Zoker\FilamentStaticPages\Components\PartnersBlock;

class FilamentStaticPagesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-static-pages')
            ->hasViews()
            ->hasConfigFile()
            ->hasViews()
            ->hasViewComponents('fsp',
                ContentBlock::class,
                PartnersBlock::class
            )
            ->hasMigrations([
                'create_zoker_pages_pages_table',
                'create_zoker_pages_blocks_table',
            ]);
    }

    public function bootingPackage()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
