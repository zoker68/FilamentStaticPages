<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\MenuResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\MenuResource;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
