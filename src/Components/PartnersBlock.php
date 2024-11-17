<?php

namespace Zoker\FilamentStaticPages\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class PartnersBlock extends BlockComponent
{
    public static string $label = 'Our Partners Block';

    public static string $viewNamespace = 'fsp';

    public function __construct(public array $data) {}

    public function render(): View
    {
        return view('filament-static-pages::components.partners', [
            'data' => $this->data,
        ]);
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('data.title')
                ->label('Title'),

            FileUpload::make('data.attachments')
                ->multiple()
                ->image()
                ->disk(config('shop.disk'))
                ->imageEditor()
                ->panelLayout('grid')
                ->reorderable()
                ->imageEditorAspectRatios([
                    null,
                    '16:9',
                    '4:3',
                    '1:1',
                ]),
        ];
    }
}
