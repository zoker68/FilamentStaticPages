<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\TextInput;
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
        ];
    }
}
