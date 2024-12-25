<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ImageWithTextBlock extends BlockComponent
{
    public static string $label = 'Image with text';

    public static string $viewTemplate = 'components.image-with-text';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-identification';

    public function render(): View
    {
        $this->data['storageUrl'] = Storage::disk(config('filament-static-pages.disk'))->url('/');

        return parent::render();
    }

    protected function getTemplate(): string
    {
        return parent::getTemplate() . '.' . $this->data['template'];
    }

    public static function getSchema(): array
    {
        return [
            Select::make('template')
                ->label('Layout')
                ->columnSpanFull()
                ->options([
                    'small-icon' => 'Small block with icon',
                    'wide-image' => 'Wide block with image',
                ])
                ->default('small-icon')
                ->required()
                ->selectablePlaceholder(false),

            Repeater::make('blocks')
                ->label('Blocks')
                ->columnSpanFull()
                ->addActionLabel('Add block')
                ->minItems(1)
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state): string => $state['heading'] ?? 'Block')
                ->columns(3)
                ->schema([
                    TextInput::make('heading')
                        ->label('Heading')
                        ->maxValue(255)
                        ->columnSpanFull(),

                    RichEditor::make('text')
                        ->label('Text')
                        ->columnStart(1)
                        ->columnSpan(2),

                    FileUpload::make('image')
                        ->label('Image')
                        ->image()
                        ->disk(config('filament-static-pages.disk'))
                        ->directory('blocks-images')
                        ->maxSize(10 * 1024)
                        ->imageEditor()
                        ->imageEditorAspectRatios([null, '4:3', '16:9', '1:1', '2:1', '3:1', '4:1']),

                    TextInput::make('link.text')
                        ->label('Link Text')
                        ->maxValue(255),
                    TextInput::make('link.url')
                        ->label('URL')
                        ->maxValue(255)
                        ->url(),

                    Select::make('link.target')
                        ->label('Link Target')
                        ->default('_self')
                        ->selectablePlaceholder(false)
                        ->options([
                            '_self' => '_self',
                            '_blank' => '_blank',
                            '_parent' => '_parent',
                            '_top' => '_top',
                        ]),
                ]),
        ];
    }
}
