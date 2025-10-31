<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\Contents\ContentResource;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
