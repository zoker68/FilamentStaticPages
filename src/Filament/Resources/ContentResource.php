<?php

namespace Zoker\FilamentStaticPages\Filament\Resources;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Models\Content;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $slug = 'contents';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Static Pages';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            'index' => \Zoker\FilamentStaticPages\Filament\Resources\ContentResource\Pages\ListContents::route('/'),
            'create' => \Zoker\FilamentStaticPages\Filament\Resources\ContentResource\Pages\CreateContent::route('/create'),
            'edit' => \Zoker\FilamentStaticPages\Filament\Resources\ContentResource\Pages\EditContent::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
