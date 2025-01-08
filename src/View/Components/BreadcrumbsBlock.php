<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Zoker\FilamentStaticPages\Classes\BlockComponent;
use Zoker\FilamentStaticPages\Models\Page;

class BreadcrumbsBlock extends BlockComponent
{
    public static string $label = 'Breadcrumbs';

    public static string $viewTemplate = 'components.breadcrumbs';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-link';

    public static function getSchema(): array
    {

        return [
            Repeater::make('breadcrumbs')
                ->label('Breadcrumbs')
                ->columnSpanFull()
                ->columns(2)
                ->default(fn (Page $page) => self::generateDefaultBreadcrumbs($page))
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required(),

                    Builder::make('url')
                        ->label('URL')
                        ->required()
                        ->reorderable(false)
                        ->maxItems(1)
                        ->schema([
                            Block::make('url')
                                ->label('URL')
                                ->schema([
                                    TextInput::make('url')
                                        ->label('URL')
                                        ->required(),
                                ]),
                            Block::make('fsp')
                                ->label('Static Page')
                                ->schema([
                                    Select::make('page')
                                        ->label('Page')
                                        ->options(Page::whereNotNull('url')->pluck('name', 'url')->toArray()),
                                ]),
                            Block::make('route')
                                ->label('Route')
                                ->schema(self::getRouteUrlSchema()),
                        ]),

                ]),
        ];
    }

    public static function getRouteOptions()
    {
        return collect(Route::getRoutes()->getRoutesByName())
            ->filter(function (\Illuminate\Routing\Route $route) {
                $excludedPrefixes = ['filament.', 'fsp.', 'debugbar.', 'livewire.'];

                return ! Str::startsWith($route->getName(), $excludedPrefixes)
                    && (in_array('GET', $route->methods() ?? []) || in_array('HEAD', $route->methods()));
            })->mapWithKeys(function ($route) {
                return [$route->getName() => $route->getName()];
            });
    }

    public static function getParamsForRoute(string $name): array
    {
        return Route::getRoutes()->getByName($name)->bindingFields();
    }

    private static function getSchemaForRoute(?string $route): ?array
    {
        $fields = [];

        if (! $route) {
            return $fields;
        }

        foreach (self::getParamsForRoute($route) as $key => $param) {
            $fields[] = TextInput::make($key)
                ->label($param);
        }

        return $fields;
    }

    public static function getRouteUrlSchema(): array
    {
        return [
            Select::make('route')
                ->label('Route')
                ->options(self::getRouteOptions())
                ->required()
                ->live(),

            Repeater::make('params')
                ->hidden(fn (Get $get) => ! $get('route') || ! count(self::getParamsForRoute($get('route'))))
                ->label('Parameters')
                ->columnSpanFull()
                ->maxItems(1)
                ->reorderable(false)
                ->schema(fn (Get $get) => self::getSchemaForRoute($get('route'))),

        ];
    }

    public static function generateDefaultBreadcrumbs(Page $page): array
    {
        $breadcrumbs = [];

        do {
            if ($page->parent) {

                $breadcrumbs[] = [
                    'title' => $page->name,
                    'url' => [
                        [
                            'type' => 'fsp',
                            'data' => [
                                'page' => $page->url,
                            ],
                        ],
                    ],
                ];
            } else {
                $breadcrumbs[] = [
                    'title' => $page->name,
                    'url' => [[
                        'type' => 'route',
                        'data' => ['route' => 'index'],
                    ], ],
                ];
            }
        } while ($page = $page->parent);

        return array_reverse($breadcrumbs);
    }

    public static function maxItem(): ?int
    {
        return 1;
    }
}
