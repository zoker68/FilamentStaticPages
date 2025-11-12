<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\View\View;
use Zoker\FilamentStaticPages\Classes\BlockComponent;
use Zoker\FilamentStaticPages\Classes\FilamentUrlSchema;
use Zoker\FilamentStaticPages\Models\Menu as MenuModel;
use Zoker\FilamentStaticPages\Models\Page;

class BreadcrumbsBlock extends BlockComponent
{
    public static ?string $label = 'Breadcrumbs';

    public static string $viewTemplate = 'components.breadcrumbs';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-link';

    public MenuModel $menu;

    public function render(): View
    {
        $this->menu = new MenuModel;

        return parent::render();
    }

    /** @return array<array-key, Component> */
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
                        ->schema(FilamentUrlSchema::getSchema()),

                ]),
        ];
    }

    /** @return array<array-key, array<string, mixed>> */
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
