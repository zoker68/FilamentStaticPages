<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\MenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableCreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\MenuResource\MenuResource;

class CreateMenu extends CreateRecord
{
    use TranslatableCreateRecord;

    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
        ];
    }
}
