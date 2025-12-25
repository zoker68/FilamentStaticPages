<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ContentBlock extends BlockComponent
{
    public static string $viewNamespace = 'fsp';

    public static string $viewTemplate = 'components.content';

    public static string $icon = 'heroicon-o-document-text';

    /** @return array<array-key, Component> */
    public static function getSchema(): array
    {
        return [
            RichEditor::make('content')
                ->label(__('fsp::lang.blocks.content'))
                ->columnSpanFull()
                ->json(false),

            TextInput::make('css_class')
                ->label(__('fsp::lang.blocks.css_class'))
                ->columnSpanFull(),
        ];
    }

    public static function getLabel(): string
    {
        return __('fsp::lang.blocks.content');
    }

    public static function getBlockHeader(array $state): string // @phpstan-ignore-line
    {
        return static::getLabel();
    }
}
