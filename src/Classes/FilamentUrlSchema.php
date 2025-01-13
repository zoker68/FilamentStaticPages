<?php

namespace Zoker\FilamentStaticPages\Classes;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Zoker\FilamentStaticPages\Models\Page;

class FilamentUrlSchema
{
    public static function getSchema(): array
    {
        return [
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
        ];
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

    private static function getRouteOptions()
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

    private static function getSchemaForRoute(?string $route): ?array
    {
        $fields = [];

        if (! $route) {
            return $fields;
        }

        foreach (self::getParamsForRoute($route) as $key => $param) {
            $fields[] = TextInput::make($key)->label($param);
        }

        return $fields;
    }

    private static function getParamsForRoute(string $name): array
    {
        return Route::getRoutes()->getByName($name)->bindingFields();
    }
}
