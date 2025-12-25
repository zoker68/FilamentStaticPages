<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\PageResource;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Traits\HasMultisiteResource;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages\CreatePage;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages\EditPage;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages\ListPages;
use Zoker\FilamentStaticPages\Models\Page;

class PageResource extends Resource
{
    use HasMultisiteResource;

    protected static ?string $model = Page::class;

    protected static ?string $slug = 'pages';

    protected static string|\UnitEnum|null $navigationGroup = 'Static Pages';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Settings')
                            ->icon('heroicon-s-cog')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                                        if (! $get('url') && $state) {
                                            $set('url', Str::slug($state));
                                        }
                                    }),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->prefix(function (): string {
                                        $site = \Zoker\FilamentMultisite\Facades\FilamentSiteManager::getCurrentSite();
                                        $baseUrl = $site->hostWithScheme;
                                        $prefix = $site->prefix ? '/' . $site->prefix : '';
                                        $routePrefix = config('fsp.route_prefix') ? '/' . config('fsp.route_prefix') : '';

                                        return $baseUrl . $prefix . $routePrefix . '/';
                                    })
                                    ->unique(modifyRuleUsing: function (Unique $rule) {
                                        return $rule->where('site_id', FilamentSiteManager::getCurrentSite()->id);
                                    }),

                                Select::make('parent_id')
                                    ->label('Parent page')
                                    ->options(
                                        fn (?Page $record): array => Page::query()
                                            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                            ->pluck('name', 'id')
                                            ->toArray()
                                    ),

                                Select::make('layout')
                                    ->label('Layout')
                                    ->required()
                                    ->default(config('fsp.layout'))
                                    ->createOptionAction(
                                        fn (Action $action) => $action->modalWidth('3xl'),
                                    )
                                    ->options(fn (): array => Layout::getLayoutOptions()),

                                Toggle::make('published')
                                    ->label('Published'),

                                TextEntry::make('created_at')
                                    ->label('Created Date')
                                    ->columnStart(1)
                                    ->state(fn (?Page $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                TextEntry::make('updated_at')
                                    ->label('Last Modified Date')
                                    ->state(fn (?Page $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                            ]),
                        Tab::make('Blocks')
                            ->icon('heroicon-s-rectangle-stack')
                            ->schema([
                                Builder::make('content')
                                    ->blockNumbers(false)
                                    ->collapsed()
                                    ->cloneable()
                                    ->blockPickerColumns(3)
                                    ->blocks(BlocksComponentRegistry::getFilamentSchema()),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('parent_id', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with(['parent', 'site']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Page $record) => $record->parent ? 'Parent page: ' . $record->parent->name : null),

                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->sortable()
                    ->url(function (Page $record): string {
                        /** @var Site $site */
                        $site = $record->site;
                        $baseUrl = $site->hostWithScheme;
                        $prefix = $site->prefix ? '/' . $site->prefix : '';
                        $routePrefix = config('fsp.route_prefix') ? '/' . config('fsp.route_prefix') : '';

                        return $baseUrl . $prefix . $routePrefix . '/' . $record->url;
                    })
                    ->openUrlInNewTab(),

                ToggleColumn::make('published')
                    ->label('Published')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
