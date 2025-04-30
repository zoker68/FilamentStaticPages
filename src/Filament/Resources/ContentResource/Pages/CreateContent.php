<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\ContentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\ContentResource;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
