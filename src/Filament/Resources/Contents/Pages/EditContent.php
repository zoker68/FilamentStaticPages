<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableEditRecord;
use Zoker\FilamentStaticPages\Filament\Actions\ContentTransferAction;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\ContentResource;

class EditContent extends EditRecord
{
    use TranslatableEditRecord;

    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
            ContentTransferAction::make(),
            DeleteAction::make(),
        ];
    }
}
