<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\View\View;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class MetaBlock extends BlockComponent
{
    public static ?string $label = 'Meta Data';

    public static string $viewTemplate = 'components.meta-data';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-code-bracket';

    /** @return array<array-key, Component> */
    public static function getSchema(): array
    {
        return [
            Group::make([
                TextInput::make('title')
                    ->label('Seo title')
                    ->live()
                    ->hint(fn ($state) => strlen($state) . ' characters')
                    ->hintColor(fn ($state) => strlen($state) > 60 ? 'danger' : null)
                    ->helperText('Recommended maximum length: 60 characters')
                    ->default(fn (Get $get) => $get('../../../name') . ' | ' . config('app.name')),
                Select::make('indexing')
                    ->label('Allow robots indexing?')
                    ->selectablePlaceholder(false)
                    ->default('index')
                    ->options([
                        'index' => 'Yes',
                        'noindex' => 'No',
                    ]),

                Select::make('follow')
                    ->label('Allow robots to follow links?')
                    ->selectablePlaceholder(false)
                    ->default('follow')
                    ->options([
                        'follow' => 'Yes',
                        'nofollow' => 'No',
                    ]),
            ]),

            Group::make(self::getDescriptionField()),
        ];
    }

    public static function maxItem(): ?int
    {
        return 1;
    }

    /** @return array<array-key, Component> */
    public static function getDescriptionField(): array
    {
        $fields = [
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->live()
                ->hint(fn ($state) => mb_strlen($state) . ' characters')
                ->hintColor(fn ($state) => mb_strlen($state) > 160 ? 'danger' : null)
                ->helperText('Recommended maximum length: 160 characters'),

            TextInput::make('canonical_url')
                ->label('Canonical URL')
                ->url(),
        ];

        if (class_exists(\Zoker\Shop\Classes\AIQuery::class)) {
            $fields[] =
                TextEntry::make('generateAI')
                    ->label('Generate SEO with AI')
                    ->key('generateAI')
                    ->hintAction(
                        Action::make('generate_ai')
                            ->label('Generate SEO')
                            ->action(function (Set $set, Get $get) {
                                $pageSettings = $get('../../../');

                                $result = (array) json_decode(\Zoker\Shop\Classes\AIQuery::seoTitleDescriptionForMetaPage($pageSettings)); // @phpstan-ignore-line

                                $set('title', $result['title'] . ' | ' . config('app.name'));
                                $set('description', $result['description']);
                            }),
                    );
        }

        return $fields;
    }

    public function render(): View
    {
        if (isset($this->data['canonical_url'])) {
            AlternateLinks::setCanonicalUrl($this->data['canonical_url']);
        }

        return parent::render();
    }
}
