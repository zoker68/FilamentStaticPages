<?php

namespace Zoker\FilamentStaticPages\Classes;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

abstract class BlockComponent extends Component
{
    public static string $viewNamespace = '';

    public static string $viewTemplate = '';

    public static string $icon;

    public function __construct(public array $data) {}

    abstract public static function getSchema(): array;

    public function render(): View
    {
        return view(static::getTemplate(), $this->data);
    }

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

    public static function getIcon(): ?string
    {
        if (! isset(static::$icon)) {
            return null;
        }

        return static::$icon;
    }

    public function getTemplate(): string
    {
        return static::getNamespace() . static::$viewTemplate;
    }

    public static function getViewComponent(): string
    {
        return static::getNamespace() . static::getComponentName();
    }

    private static function getNamespace(): string
    {
        return ! empty(static::$viewNamespace)
            ? static::$viewNamespace . '::'
            : '';
    }

    public static function getComponentName(): string
    {
        return Str::of(static::class)->after('\\Components\\')->replace('\\', '.');
    }
}
