<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\ContentResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zoker\FilamentStaticPages\Filament\Resources\ContentResource;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
