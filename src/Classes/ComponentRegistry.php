<?php

namespace Zoker\FilamentStaticPages\Classes;

use Zoker\FilamentStaticPages\Components\ContentBlock;
use Zoker\FilamentStaticPages\Components\PartnersBlock;

class ComponentRegistry
{
    public static array $components = [
        'Content' => ContentBlock::class,
        'Partners' => PartnersBlock::class,
    ];

    public static function register(string $name, string $component): void
    {
        static::$components[$name] = $component;
    }

    public static function has(string $name): bool
    {
        return array_key_exists($name, static::$components);
    }

    public static function get(string $name): ?string
    {
        return static::$components[$name] ?? null;
    }

    public static function getComponents(): array
    {
        return static::$components;
    }

    public static function getOptions(): array
    {
        $options = [];
        foreach (static::$components as $name => $component) {
            $options[$name] = $component::getLabel();
        }

        return $options;
    }

    public static function getComponent(string $name): ?string
    {
        return static::$components[$name] ?? null;
    }
}
