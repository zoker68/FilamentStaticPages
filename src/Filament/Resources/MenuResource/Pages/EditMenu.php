<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\MenuResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableEditRecord;
use Zoker\FilamentStaticPages\Filament\Resources\MenuResource\MenuResource;

class EditMenu extends EditRecord
{
    use TranslatableEditRecord;

    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
