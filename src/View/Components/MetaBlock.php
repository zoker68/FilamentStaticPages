<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class MetaBlock extends BlockComponent
{
    public static string $label = 'Meta Data';

    public static string $viewTemplate = 'components.meta-data';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-code-bracket';

    public static function getSchema(): array
    {
        return [
            Group::make([
                TextInput::make('title')
                    ->label('Seo title')
                    ->live()
                    ->hint(fn ($state) => strlen($state) . ' characters')
                    ->hintColor(fn ($state) => strlen($state) > 60 ? 'danger' : null)
                    ->helperText('Recommended maximum length: 60 characters')
                    ->default(fn (Get $get) => $get('../../../name') . ' | ' . config('app.name')),
                Select::make('indexing')
                    ->label('Allow robots indexing?')
                    ->selectablePlaceholder(false)
                    ->default('index')
                    ->options([
                        'index' => 'Yes',
                        'noindex' => 'No',
                    ]),

                Select::make('follow')
                    ->label('Allow robots to follow links?')
                    ->selectablePlaceholder(false)
                    ->default('follow')
                    ->options([
                        'follow' => 'Yes',
                        'nofollow' => 'No',
                    ]),
            ]),

            Textarea::make('description')
                ->label('Description')
                ->rows(8)
                ->live()
                ->hint(fn ($state) => strlen($state) . ' characters')
                ->hintColor(fn ($state) => strlen($state) > 160 ? 'danger' : null)
                ->helperText('Recommended maximum length: 160 characters'),
        ];
    }

    public static function maxItem(): ?int
    {
        return 1;
    }
}
