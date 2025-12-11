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
            ->hasConfigFile()
            ->hasViews('fsp')
            ->hasMigrations([
                'create_pages_table',
                'add_parent_id_field_to_pages_table',
                'create_menu_table',
                'create_content_table',
                'add_site_id_to_pages_table',
            ]);

        Blade::componentNamespace('Zoker\\FilamentStaticPages\\View\\Components', 'fsp');

        Blade::directive('fspContent', function ($code) {
            $code = trim($code, '\'"');

            return "<?php echo \Blade::render('<x-fsp::render-content-directive code=\"$code\" />'); ?>";
        });

        Blade::directive('fspMenu', function ($code) {
            $code = trim($code, '\'"');

            return "<?php echo \Blade::render('<x-fsp::menu code=\"$code\" />'); ?>";
        });

    }

    public function bootingPackage(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
