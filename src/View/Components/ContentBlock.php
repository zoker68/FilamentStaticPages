<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ContentBlock extends BlockComponent
{
    public static ?string $label = 'HTML Block';

    public static string $viewNamespace = 'fsp';

    public static string $viewTemplate = 'components.content';

    public static string $icon = 'heroicon-o-document-text';

    /** @return array<array-key, Component> */
    public static function getSchema(): array
    {
        return [
            RichEditor::make('content')
                ->label('Content')
                ->columnSpanFull(),

            TextInput::make('css_class')
                ->label('CSS Class')
                ->columnSpanFull(),
        ];
    }

    public static function getBlockHeader(array $state): string // @phpstan-ignore-line
    {
        return static::getLabel();
    }
}
