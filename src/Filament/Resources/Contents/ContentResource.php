<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\Contents;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages\CreateContent;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages\EditContent;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages\ListContents;
use Zoker\FilamentStaticPages\Models\Content;

class ContentResource extends Resource
{
    use Translatable;

    protected static ?string $model = Content::class;

    protected static ?string $slug = 'contents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Static Pages';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->required(),

                Builder::make('content')
                    ->columnSpanFull()
                    ->blockNumbers(false)
                    ->collapsed()
                    ->cloneable()
                    ->blockPickerColumns(3)
                    ->blocks(BlocksComponentRegistry::getFilamentSchema()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code'),
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
            'index' => ListContents::route('/'),
            'create' => CreateContent::route('/create'),
            'edit' => EditContent::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
