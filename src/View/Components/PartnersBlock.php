<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class PartnersBlock extends BlockComponent
{
    public static string $label = 'Our Partners Block';

    public static string $viewNamespace = 'fsp';

    public static string $viewTemplate = 'components.partners';

    public static string $icon = 'heroicon-o-users';

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Title'),

            FileUpload::make('attachments')
                ->multiple()
                ->image()
                ->imageEditor()
                ->panelLayout('grid')
                ->reorderable()
                ->imageEditorAspectRatios([
                    null,
                    '16:9',
                    '4:3',
                    '1:1',
                ])
                ->columnSpanFull(),
        ];
    }
}
