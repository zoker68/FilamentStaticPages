<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Translatable\TranslatableServiceProvider;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteRouteServiceProvider;
use Zoker\FilamentMultisite\Providers\FilamentMultisiteServiceProvider;
use Zoker\FilamentStaticPages\FilamentStaticPagesServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The HasMultisite global scope calls Filament::isServing(); avoid booting the whole
        // Filament panel by stubbing it to false so the SiteManager path is used.
        $this->mock(\Filament\Filament::class, function ($mock) {
            $mock->shouldReceive('isServing')->andReturn(false);
        });
        Filament::swap(app(\Filament\Filament::class));

        // Package migrations have no timestamp prefixes, so load each file explicitly in
        // dependency order (globbing a directory would sort them alphabetically).
        $multisiteMigrations = __DIR__ . '/../../FilamentMultisite/database/migrations';
        $this->loadMigrationsFrom($multisiteMigrations . '/create_sites_table.php');
        $this->loadMigrationsFrom($multisiteMigrations . '/add_label_to_sites_table.php');

        $migrations = __DIR__ . '/../database/migrations';
        foreach ([
            'create_pages_table',
            'add_parent_id_field_to_pages_table',
            'add_site_id_to_pages_table',
            'create_menu_table',
            'create_content_table',
        ] as $migration) {
            $this->loadMigrationsFrom($migrations . '/' . $migration . '.php');
        }

        $this->resetSiteStaticCaches();
    }

    protected function resetSiteStaticCaches(): void
    {
        $reflection = new \ReflectionClass(Site::class);
        foreach (['sitesForDomain', 'usingLocales'] as $property) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue(null);
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            TranslatableServiceProvider::class,
            AiServiceProvider::class,
            FilamentMultisiteServiceProvider::class,
            FilamentMultisiteRouteServiceProvider::class,
            FilamentStaticPagesServiceProvider::class,
        ];
    }
}
