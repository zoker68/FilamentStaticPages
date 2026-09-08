<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentStaticPages\Ai\Agents\SeoAgent;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class MetaBlock extends BlockComponent
{
    public static ?string $label = 'Meta Data';

    /** @var array<int, string> */
    public static array $translatable = ['title', 'description'];

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
                    ->default(fn (Get $get) => (string) $get('../../../name')),
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

            FileUpload::make('social_image')
                ->label('Social share image')
                ->helperText('Shown in link previews (og:image). Recommended 1200×630 px.')
                ->image()
                ->disk(config('fsp.disk'))
                ->directory('meta')
                ->maxSize(5 * 1024)
                ->imageEditor()
                ->imageEditorAspectRatioOptions([null, '1200:630'])
                ->columnSpanFull(),
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

        if (config('fsp.ai.enabled')) {
            $fields[] =
                TextEntry::make('generateAI')
                    ->label('Generate SEO with AI')
                    ->key('generateAI')
                    ->hintAction(
                        Action::make('generate_ai')
                            ->label('Generate SEO')
                            ->action(function (Set $set, Get $get) {
                                $pageSettings = $get('../../../');

                                $response = (new SeoAgent(FilamentSiteManager::getCurrentSiteLocale()))->prompt(
                                    json_encode($pageSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                                    provider: config('fsp.ai.provider'),
                                    model: config('fsp.ai.model'),
                                );

                                $set('title', (string) $response['title']);
                                $set('description', (string) $response['description']);
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

        $this->data['ogUrl'] = AlternateLinks::getCanonicalUrl();
        $this->data['ogImage'] = $this->getSocialImageUrl();
        $this->data['siteName'] = (string) config('fsp.site_name');
        $this->data['ogLocale'] = config('fsp.og_locales.' . app()->getLocale()) ?? app()->getLocale();

        return parent::render();
    }

    /**
     * Absolute URL of the page's social image, or the configured fallback; null when neither exists.
     */
    public function getSocialImageUrl(): ?string
    {
        $image = $this->data['social_image'] ?? null;

        if (is_string($image) && $image !== '') {
            $url = Str::startsWith($image, ['http://', 'https://'])
                ? $image
                : Storage::disk(config('fsp.disk'))->url($image);
        } else {
            $url = config('fsp.og_image_url');
        }

        if (! is_string($url) || $url === '') {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
