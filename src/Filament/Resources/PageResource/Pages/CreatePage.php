<?php

namespace Zoker\FilamentStaticPages\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;
use Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableCreateRecord;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\PageResource;

class CreatePage extends CreateRecord
{
    use TranslatableCreateRecord;

    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['url'] === 'index') {
            $data['parent_id'] = null;
            $data['published'] = false;
        }

        $data['site_id'] = FilamentSiteManager::getCurrentSite()->id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            SiteSwitcher::make(),
        ];
    }
}
