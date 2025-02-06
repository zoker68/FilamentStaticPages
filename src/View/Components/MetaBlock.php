<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class MetaBlock extends BlockComponent
{
    public static string $label = 'Meta Data';

    public static string $viewTemplate = 'components.meta-data';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-code-bracket';

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

    public static function getDescriptionField(): array
    {
        $fiedls = [
            Textarea::make('description')
                ->label('Description')
                ->rows(6)
                ->live()
                ->hint(fn ($state) => mb_strlen($state) . ' characters')
                ->hintColor(fn ($state) => mb_strlen($state) > 160 ? 'danger' : null)
                ->helperText('Recommended maximum length: 160 characters'),
        ];

        if (class_exists(\Zoker\Shop\Classes\AIQuery::class)) {
            $fiedls[] =
                Placeholder::make('generateAI')
                    ->label('Generate SEO with AI')
                    ->key('generateAI')
                    ->hintAction(
                        Action::make('generate_ai')
                            ->label('Generate SEO')
                            ->action(function (Set $set, Get $get) {
                                $pageSettings = $get('../../../');

                                $result = (array) json_decode(\Zoker\Shop\Classes\AIQuery::seoTitleDescriptionForMetaPage($pageSettings));

                                $set('title', $result['title'] . ' | ' . config('app.name'));
                                $set('description', $result['description']);
                            }),
                    );
        }

        return $fiedls;
    }
}
