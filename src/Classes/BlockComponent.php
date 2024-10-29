<?php

namespace Zoker\FilamentStaticPages\Classes;

use Illuminate\Support\Str;
use Illuminate\View\Component;

abstract class BlockComponent extends Component
{
    public static string $viewNamespace = '';

    abstract public static function getSchema(): array;

    public static function getLabel(): string
    {
        if (isset(static::$label)) {
            return static::$label;
        }

        $className = class_basename(static::class);

        return Str::of($className)
            ->replaceLast('Block', '')
            ->headline();
    }

    public static function getViewComponent(): string
    {
        return static::getNamespace() . static::getComponentKebabName();
    }

    private static function getNamespace(): string
    {
        return ! empty(static::$viewNamespace)
            ? static::$viewNamespace . '-'
            : '';
    }

    public static function getComponentKebabName(): string
    {
        return Str::of(class_basename(static::class))->kebab();
    }
}
