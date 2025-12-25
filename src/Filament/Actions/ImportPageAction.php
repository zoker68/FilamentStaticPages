<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;

class ImportPageAction extends AbstractImportExportAction
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('importPage');
    }

    /**
     * @return array<int, Field>
     */
    protected function getAdditionalFormFields(): array
    {
        return [
            Toggle::make('publish')
                ->label(__('fsp::lang.actions.publish_after_import'))
                ->default(false),
        ];
    }

    /**
     * @param  array<string, mixed>  $importData
     * @param  array<string, mixed>  $data
     */
    protected function processImportData(array $importData, array $data, BlocksExportImportService $service): void
    {
        $currentSite = FilamentSiteManager::getCurrentSite();
        $page = $service->importAsPage($importData, $currentSite, $data['publish'] ?? false);

        $this->showSuccessNotification(
            __('fsp::lang.messages.page_imported', ['name' => $page->name])
        );
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'importPage');
    }
}
