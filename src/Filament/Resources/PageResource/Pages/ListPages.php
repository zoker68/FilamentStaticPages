<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableListRecord;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\PageResource;

class ListPages extends ListRecords
{
    use TranslatableListRecord;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
