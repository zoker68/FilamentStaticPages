<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\MenuResource;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Zoker\FilamentStaticPages\Classes\FilamentUrlSchema;
use Zoker\FilamentStaticPages\Filament\Resources\MenuResource\Pages\CreateMenu;
use Zoker\FilamentStaticPages\Filament\Resources\MenuResource\Pages\EditMenu;
use Zoker\FilamentStaticPages\Filament\Resources\MenuResource\Pages\ListMenus;
use Zoker\FilamentStaticPages\Models\Menu;

class MenuResource extends Resource
{
    use Translatable;

    protected static ?string $model = Menu::class;

    protected static ?string $slug = 'menus';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Static Pages';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->columnSpanFull()
                    ->label('Menu items')
                    ->schema(self::getItemShema()),
            ]);
    }

    /**
     * @return array<array-key, Component>
     */
    public static function getItemShema(): array
    {
        return [
            TextInput::make('label')
                ->label('Label')
                ->required(),

            TextInput::make('css')
                ->label('CSS'),

            Builder::make('url')
                ->label('URL')
                ->reorderable(false)
                ->maxItems(1)
                ->schema(FilamentUrlSchema::getSchema()),

            Toggle::make('hasSubmenu')
                ->default(false)
                ->live()
                ->label('Has submenu'),

            Repeater::make('submenu')
                ->live()
                ->columnSpanFull()
                ->hidden(fn (Get $get) => $get('hasSubmenu') === false)
                ->label('Menu items')
                ->schema(fn (Get $get) => $get('hasSubmenu') === true ? self::getItemShema() : []),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->label('Code'),
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
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
