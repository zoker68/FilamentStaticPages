<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class HeadingBlock extends BlockComponent
{
    public static string $label = 'Heading';

    public static string $viewTemplate = 'components.heading';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-exclamation-triangle';

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label('Heading')
                ->required()
                ->columnSpanFull(),

            Select::make('size')
                ->label('Size')
                ->options([
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                ])
                ->selectablePlaceholder(false)
                ->required(),

            TextInput::make('css_class')
                ->label('CSS Class'),
        ];
    }

    public static function getBlockHeader(array $state): string
    {
        return static::getLabel() . ($state['heading'] ? ' | ' . Str::of($state['heading'])->limit(60) : '');
    }
}
