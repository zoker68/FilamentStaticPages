<?php

namespace Zoker\FilamentStaticPages\Classes;

use Filament\Forms\Components\Builder\Block;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Zoker\FilamentStaticPages\Models\Page;

abstract class BlockComponent extends Component
{
    public static string $viewNamespace = '';

    public static string $viewTemplate = '';

    public static string $icon;

    public static ?string $label = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public array $data, public ?Page $page = null) {}

    /**
     * @return array<array-key, Block>
     */
    abstract public static function getSchema(): array;

    public function render(): View
    {
        return view(static::getTemplate(), $this->data); // @phpstan-ignore-line
    }

    public static function getLabel(): string
    {
        if (static::$label) {
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

    protected function getTemplate(): string
    {
        return static::getNamespace() . static::$viewTemplate;
    }

    public static function getViewComponent(): string
    {
        return static::getNamespace() . static::getComponentName();
    }

    protected static function getNamespace(): string
    {
        return ! empty(static::$viewNamespace)
            ? static::$viewNamespace . '::'
            : '';
    }

    public static function getComponentName(): string
    {
        return Str::of(static::class)->after('\\Components\\')->replace('\\', '.');
    }

    public static function maxItem(): ?int
    {
        return null;
    }
}
