<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class SliderBlock extends BlockComponent
{
    public static ?string $label = 'Slider (Blocks)';

    public static string $viewTemplate = 'components.slider';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-c-chevron-double-right';

    public function render(): View
    {
        $this->data['storageUrl'] = Storage::disk(config('filament-static-pages.disk'))->url('/');

        if ($this->data['only_images'] ?? false) {
            $this->data['slides'] = [];
            foreach ($this->data['gallery'] as $image) {
                $this->data['slides'][]['image'] = $image;
            }
        }

        return parent::render();
    }

    /** @return array<array-key, Component> */
    public static function getSchema(): array
    {
        return [
            Select::make('template')
                ->label('Layout')
                ->columnSpanFull()
                ->options([
                    'default' => 'Default (simple slider)',
                    'block' => 'Block (Features)',
                ])
                ->default('default')
                ->required()
                ->selectablePlaceholder(false),

            Toggle::make('only_images')
                ->label('Only images')
                ->default(false)
                ->live(),

            FileUpload::make('gallery')
                ->label('Gallery')
                ->multiple()
                ->hidden(fn (Get $get) => ! $get('only_images'))
                ->image()
                ->columnSpanFull()
                ->disk(config('filament-static-pages.disk'))
                ->directory('sliders')
                ->maxSize(10 * 1024)
                ->imageEditor()
                ->reorderable()
                ->panelLayout('grid')
                ->imageEditorAspectRatios([null, '4:3', '16:9', '1:1', '2:1', '3:1', '4:1']),

            Repeater::make('slides')
                ->hidden(fn (Get $get) => $get('only_images'))
                ->label('Slides')
                ->columnSpanFull()
                ->addActionLabel('Add slide')
                ->minItems(1)
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state): string => $state['heading'] ?? 'Slide')
                ->columns(3)
                ->schema([
                    'heading' => TextInput::make('heading')
                        ->label('Heading')
                        ->maxValue(255)
                        ->columnSpanFull(),

                    'button' => TextInput::make('button')
                        ->label('Button text')
                        ->maxValue(255),

                    'link' => TextInput::make('link')
                        ->label('URL')
                        ->maxValue(255)
                        ->url(),

                    'target' => Select::make('target')
                        ->label('Link Target')
                        ->default('_self')
                        ->selectablePlaceholder(false)
                        ->options([
                            '_self' => '_self',
                            '_blank' => '_blank',
                            '_parent' => '_parent',
                            '_top' => '_top',
                        ]),

                    'text' => RichEditor::make('text')
                        ->label('Text')
                        ->columnStart(1)
                        ->columnSpan(2),

                    'image' => FileUpload::make('image')
                        ->label('Image')
                        ->image()
                        ->disk(config('filament-static-pages.disk'))
                        ->directory('sliders')
                        ->maxSize(10 * 1024)
                        ->imageEditor()
                        ->imageEditorAspectRatios([null, '4:3', '16:9', '1:1', '2:1', '3:1', '4:1']),
                ]),
        ];
    }
}
