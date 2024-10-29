<?php

namespace Zoker\FilamentStaticPages\Filament\Resources;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
use Zoker\FilamentStaticPages\Classes\ComponentRegistry;
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
                                        return route('filament-static-pages.page', ['page' => '/']) . '/';
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
                                Repeater::make('blocks')
                                    ->relationship('blocks')
                                    ->label('Blocks')
                                    ->addActionLabel('Add Block')
                                    ->reorderable('sort')
                                    ->orderColumn('sort')
                                    ->collapsed(true)
                                    ->itemLabel(function (array $state): ?string {
                                        if (isset($state['component']) && ComponentRegistry::has($state['component'])) {
                                            /** @var \Zoker\FilamentStaticPages\Classes\BlockComponent $blockComponent */
                                            $blockComponent = ComponentRegistry::get($state['component']);

                                            return $blockComponent::getLabel();
                                        }

                                        return $state['component'] ?? null;
                                    })
                                    ->cloneable()
                                    ->reorderableWithButtons()
                                    ->live()
                                    ->schema([
                                        Select::make('component')
                                            ->label('Component')
                                            ->required()
                                            ->live()
                                            ->preload()
                                            ->options(ComponentRegistry::getOptions()),

                                        Group::make(function (Get $get, array $state) {
                                            $component = $get('component');
                                            if (! $component || ! ComponentRegistry::has($component)) {
                                                return [];
                                            }

                                            if (! isset($state['id'])) {
                                                return [
                                                    Placeholder::make('')
                                                        ->content('Save changes before editing block settings'),
                                                ];
                                            }

                                            /** @var \Zoker\FilamentStaticPages\Classes\BlockComponent $blockComponent */
                                            $blockComponent = ComponentRegistry::get($component);

                                            return $blockComponent::getSchema();
                                        }),

                                    ])
                                    ->deleteAction(
                                        fn (Action $action) => $action->requiresConfirmation(),
                                    ),
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
                    ->url(fn (Page $record) => route('filament-static-pages.page', $record))
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
