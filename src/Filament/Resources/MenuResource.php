<?php

namespace Zoker\FilamentStaticPages\Filament\Resources;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zoker\FilamentStaticPages\Classes\FilamentUrlSchema;
use Zoker\FilamentStaticPages\Filament\Resources;
use Zoker\FilamentStaticPages\Models\Menu;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $slug = 'menus';

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Static Pages';

    public static function form(Form $form): Form
    {
        $schema = [
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

        ];

        return $form
            ->schema($schema);
    }

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
            'index' => Resources\MenuResource\Pages\ListMenus::route('/'),
            'create' => Resources\MenuResource\Pages\CreateMenu::route('/create'),
            'edit' => Resources\MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
