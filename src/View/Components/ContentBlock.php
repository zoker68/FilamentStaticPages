<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\RichEditor;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ContentBlock extends BlockComponent
{
    public static string $label = 'HTML Block';

    public static string $viewNamespace = 'fsp';

    public static string $viewTemplate = 'components.content';

    public static function getSchema(): array
    {
        return [
            RichEditor::make('data.content')
                ->label('Content')
                ->columnSpanFull(),
        ];
    }
}
