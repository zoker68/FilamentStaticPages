<?php

namespace Zoker\FilamentStaticPages\Filament\Resources;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Classes\Layout;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages;
use Zoker\FilamentStaticPages\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'pages';

    protected static ?string $navigationGroup = 'Static Pages';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Settings')
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
                                    ->required()
                                    ->prefix(function (): string {
                                        return url(config('filament-static-pages.route_prefix')) . '/';
                                    })
                                    ->unique(ignoreRecord: true),

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
                        Tabs\Tab::make('Blocks')
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
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
