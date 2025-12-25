<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;

class ImportContentAction extends AbstractImportExportAction
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('importContent');
    }

    /**
     * @return array<int, Field>
     */
    protected function getAdditionalFormFields(): array
    {
        return [
            Select::make('target_locale')
                ->label(__('fsp::lang.form.target_language'))
                ->options($this->getLocaleOptions())
                ->required()
                ->default($this->getCurrentLocale())
                ->helperText(__('fsp::lang.form.language_to_import')),
        ];
    }

    /**
     * @param  array<string, mixed>  $importData
     * @param  array<string, mixed>  $data
     */
    protected function processImportData(array $importData, array $data, BlocksExportImportService $service): void
    {
        $targetLocale = $data['target_locale'] ?? $this->getCurrentLocale();
        $content = $service->importAsContent($importData, $targetLocale);

        $this->showSuccessNotification(
            __('fsp::lang.messages.content_imported', [
                'code' => $content->code,
                'locale' => strtoupper($targetLocale),
            ])
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getLocaleOptions(): array
    {
        $locales = Site::getUsingLocales();

        return array_combine($locales, array_map(fn ($locale) => strtoupper($locale), $locales));
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'importContent');
    }
}
