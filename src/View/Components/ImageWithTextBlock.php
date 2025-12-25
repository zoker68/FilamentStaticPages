<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ImageWithTextBlock extends BlockComponent
{
    public static string $viewTemplate = 'components.image-with-text';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-identification';

    public function render(): View
    {
        $this->data['storageUrl'] = Storage::disk(config('fsp.disk'))->url('/');

        return parent::render();
    }

    protected function getTemplate(): string
    {
        return parent::getTemplate() . '.' . $this->data['template'];
    }

    /** @return array<array-key, Component> */
    public static function getSchema(): array
    {
        return [
            Select::make('template')
                ->label(__('fsp::lang.blocks.layout'))
                ->columnSpanFull()
                ->options([
                    'small-icon' => __('fsp::lang.blocks.small_block_with_icon'),
                    'wide-image' => __('fsp::lang.blocks.wide_block_with_image'),
                ])
                ->default('small-icon')
                ->required()
                ->selectablePlaceholder(false),

            Repeater::make('blocks')
                ->label(__('fsp::lang.blocks.blocks'))
                ->columnSpanFull()
                ->addActionLabel(__('fsp::lang.blocks.add_block'))
                ->minItems(1)
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state): string => $state['heading'] ?? 'Block')
                ->columns(3)
                ->schema([
                    TextInput::make('heading')
                        ->label(__('fsp::lang.blocks.heading'))
                        ->maxValue(255)
                        ->columnSpanFull(),

                    RichEditor::make('text')
                        ->label(__('fsp::lang.blocks.text'))
                        ->columnStart(1)
                        ->columnSpan(2)
                        ->json(false),

                    FileUpload::make('image')
                        ->label(__('fsp::lang.blocks.image'))
                        ->image()
                        ->disk(config('fsp.disk'))
                        ->directory('blocks-images')
                        ->maxSize(10 * 1024)
                        ->imageEditor()
                        ->imageEditorAspectRatios([null, '4:3', '16:9', '1:1', '2:1', '3:1', '4:1']),

                    TextInput::make('link.text')
                        ->label(__('fsp::lang.blocks.link_text'))
                        ->maxValue(255),
                    TextInput::make('link.url')
                        ->label(__('fsp::lang.blocks.url'))
                        ->maxValue(255)
                        ->url(),

                    Select::make('link.target')
                        ->label(__('fsp::lang.blocks.link_target'))
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

    public static function getLabel(): string
    {
        return __('fsp::lang.blocks.image_with_text');
    }

    public static function getBlockHeader(array $state): string // @phpstan-ignore-line
    {
        if (count($state['blocks']) === 0) {
            return static::getLabel();
        }

        $firstBlock = reset($state['blocks']);

        return static::getLabel() . (isset($firstBlock['heading']) ? ' | ' . Str::of($firstBlock['heading'])->limit(60) : '');
    }
}
