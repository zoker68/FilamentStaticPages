<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Zoker\FilamentStaticPages\Filament\Actions\PageTransferAction;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['url'] === 'index') {
            $data['parent_id'] = null;
            $data['published'] = false;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            PageTransferAction::make(),
            DeleteAction::make(),
        ];
    }
}
