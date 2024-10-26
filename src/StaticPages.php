<?php

namespace Zoker\FilamentStaticPages;

use Filament\Contracts\Plugin;
use Filament\Panel;

class StaticPages implements Plugin
{
    public static function make()
    {
        return new static;
    }

    public function getId(): string
    {
        return 'filament-static-pages';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
        ]);
    }

    public function boot(Panel $panel): void {}
}
