<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableListRecord;
use Zoker\FilamentStaticPages\Filament\Actions\ImportContentAction;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\ContentResource;

class ListContents extends ListRecords
{
    use TranslatableListRecord;

    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
            ImportContentAction::make(),
            CreateAction::make(),
        ];
    }
}
