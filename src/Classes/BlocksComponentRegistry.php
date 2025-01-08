<?php

namespace Zoker\FilamentStaticPages\Classes;

use Filament\Forms\Components\Builder\Block;
use Zoker\FilamentStaticPages\View\Components\BannerBlock;
use Zoker\FilamentStaticPages\View\Components\BreadcrumbsBlock;
use Zoker\FilamentStaticPages\View\Components\ContentBlock;
use Zoker\FilamentStaticPages\View\Components\HeadingBlock;
use Zoker\FilamentStaticPages\View\Components\ImageWithTextBlock;
use Zoker\FilamentStaticPages\View\Components\MetaBlock;
use Zoker\FilamentStaticPages\View\Components\PartnersBlock;
use Zoker\FilamentStaticPages\View\Components\QuestionAnswerBlock;
use Zoker\FilamentStaticPages\View\Components\SliderBlock;

class BlocksComponentRegistry
{
    public static array $components = [
        'Content' => ContentBlock::class,
        'Partners' => PartnersBlock::class,
        'Slider' => SliderBlock::class,
        'ImageWithText' => ImageWithTextBlock::class,
        'Banner' => BannerBlock::class,
        'Heading' => HeadingBlock::class,
        'QuestionAnswer' => QuestionAnswerBlock::class,
        'Meta Data' => MetaBlock::class,
        'Breadcrumbs' => BreadcrumbsBlock::class,
    ];

    public static function register(string $component, ?string $name = null): void
    {
        $name ??= class_basename($component);
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

    public static function getFilamentSchema(): array
    {
        $options = [];

        foreach (static::$components as $name => $component) {
            $options[] = Block::make($name)
                ->label($component::getLabel())
                ->schema($component::getSchema())
                ->icon($component::getIcon())
                ->columns(2)
                ->maxItems($component::maxItem());
        }

        return $options;
    }

    public static function getComponent(string $name): ?string
    {
        return static::$components[$name] ?? null;
    }
}
