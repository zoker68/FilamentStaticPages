<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class BannerBlock extends BlockComponent
{
    public static ?string $label = 'Banner';

    public static string $viewTemplate = 'components.banner';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-megaphone';

    public function render(): View
    {
        $this->data['storageUrl'] = Storage::disk(config('filament-static-pages.disk'))->url('/');

        return parent::render();
    }

    /** @return array<array-key, Component> */
    public static function getSchema(): array
    {
        return [
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

            'alt' => TextInput::make('alt')
                ->label('Alt text')
                ->maxValue(255)
                ->columnSpanFull(),

            'image' => FileUpload::make('image')
                ->label('Image')
                ->image()
                ->disk(config('filament-static-pages.disk'))
                ->directory('banners')
                ->maxSize(10 * 1024)
                ->imageEditor()
                ->imageEditorAspectRatios([null, '4:3', '16:9', '1:1', '2:1', '3:1', '4:1'])
                ->columnSpanFull(),
        ];
    }

    public static function getBlockHeader(array $state): string // @phpstan-ignore-line
    {
        return static::getLabel() . ($state['alt'] ? ' | ' . $state['alt'] : '');
    }
}
