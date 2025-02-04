<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Str;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ContentBlock extends BlockComponent
{
    public static string $label = 'HTML Block';

    public static string $viewNamespace = 'fsp';

    public static string $viewTemplate = 'components.content';

    public static string $icon = 'heroicon-o-document-text';

    public static function getSchema(): array
    {
        return [
            RichEditor::make('content')
                ->label('Content')
                ->columnSpanFull(),
        ];
    }

    public static function getBlockHeader(array $state): string
    {
        return static::getLabel() . ($state['content'] ? ' | ' . Str::of($state['content'])->stripTags()->limit(60) : '');
    }
}
