<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\PageResource;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages\CreatePage;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages\EditPage;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages\ListPages;
use Zoker\FilamentStaticPages\Models\Page;

class PageResource extends Resource
{
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
                                        return url(config('filament-static-pages.route_prefix')) . '/';
                                    })
                                    ->unique(ignoreRecord: true),

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
                                    ->default(config('filament-static-pages.layout'))
                                    ->createOptionAction(
                                        fn (Action $action) => $action->modalWidth('3xl'),
                                    )
                                    ->options(fn (): array => Layout::getLayoutOptions()),

                                Toggle::make('published')
                                    ->label('Published'),

                                Placeholder::make('created_at')
                                    ->label('Created Date')
                                    ->columnStart(1)
                                    ->content(fn (?Page $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                Placeholder::make('updated_at')
                                    ->label('Last Modified Date')
                                    ->content(fn (?Page $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

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
            ->modifyQueryUsing(fn ($query) => $query->with('parent'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Page $record) => $record->parent ? 'Parent page: ' . $record->parent->name : null),

                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Page $record) => url(config('filament-static-pages.route_prefix')) . '/' . $record->url)
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
