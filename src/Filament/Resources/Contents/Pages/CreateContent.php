<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableCreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\ContentResource;

class CreateContent extends CreateRecord
{
    use TranslatableCreateRecord;

    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
        ];
    }
}
