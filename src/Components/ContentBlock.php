<?php

namespace Zoker\FilamentStaticPages\Components;

use Filament\Forms\Components\RichEditor;
use Illuminate\Contracts\View\View;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class ContentBlock extends BlockComponent
{
    public static string $label = 'HTML Block';

    public static string $viewNamespace = 'fsp';

    public function __construct(public array $data) {}

    public function render(): View
    {
        return view('filament-static-pages::components.content', [
            'data' => $this->data,
        ]);
    }

    public static function getSchema(): array
    {
        return [
            RichEditor::make('data.content')
                ->label('Content')
                ->live(),
        ];
    }
}
