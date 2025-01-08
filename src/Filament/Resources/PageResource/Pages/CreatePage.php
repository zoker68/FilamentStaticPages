<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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

        ];
    }
}
