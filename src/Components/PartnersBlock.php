<?php

namespace Zoker\FilamentStaticPages\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class PartnersBlock extends BlockComponent
{
    public static string $label = 'Our Partners Block';

    public static string $viewNamespace = 'fsp';

    public static string $viewTemplate = 'filament-static-pages::components.partners';

    public static function getSchema(): array
    {
        return [
            TextInput::make('data.title')
                ->label('Title'),

            FileUpload::make('data.attachments')
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
