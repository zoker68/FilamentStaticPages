<?php

namespace Zoker\FilamentStaticPages;

use Filament\Contracts\Plugin;
use Filament\Panel;

class StaticPages implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'filament-static-pages';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(in: __DIR__ . '/../src/Filament/Resources', for: 'Zoker\\FilamentStaticPages\\Filament\\Resources');
    }

    public function boot(Panel $panel): void {}
}
