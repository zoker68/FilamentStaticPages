<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\ContentResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Zoker\FilamentStaticPages\Filament\Resources\ContentResource;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
