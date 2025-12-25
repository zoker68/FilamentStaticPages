<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;

class PageTransferAction extends AbstractTransferAction
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('pageTransfer');
        $this->label(__('fsp::lang.actions.transfer_page'));
    }

    /**
     * @return array<string, string>
     */
    protected function getActionOptions(): array
    {
        return [
            'export' => __('fsp::lang.actions.export_to_json'),
            'import' => __('fsp::lang.actions.import_from_json'),
            'copy_new' => __('fsp::lang.actions.copy_to_new_page'),
            'copy_existing' => __('fsp::lang.actions.copy_to_existing_page'),
        ];
    }

    /**
     * @return array<Field>
     */
    protected function getAdditionalFormFields(): array
    {
        return [
            Select::make('target_site')
                ->label(__('fsp::lang.form.target_site'))
                ->options(fn () => $this->getSiteOptions())
                ->required(fn ($get) => in_array($get('action_type'), ['copy_new', 'copy_existing']))
                ->hidden(fn ($get) => in_array($get('action_type'), ['export', 'import'])),

            Select::make('target_page')
                ->label(__('fsp::lang.form.target_page'))
                ->options(fn () => $this->getPageOptions())
                ->required(fn ($get) => $get('action_type') === 'copy_existing')
                ->hidden(fn ($get) => $get('action_type') !== 'copy_existing')
                ->searchable(),

            Radio::make('copy_mode')
                ->label(__('fsp::lang.form.import_mode'))
                ->options([
                    'replace' => __('fsp::lang.form.replace_existing_blocks'),
                    'append' => __('fsp::lang.form.append_to_existing_blocks'),
                ])
                ->default('replace')
                ->required(fn ($get) => in_array($get('action_type'), ['import', 'copy_existing']))
                ->hidden(fn ($get) => $get('action_type') === 'export'),

            Toggle::make('publish')
                ->label(__('fsp::lang.actions.publish_after_copy'))
                ->default(false)
                ->hidden(fn ($get) => ! in_array($get('action_type'), ['copy_new', 'copy_existing'])),
        ];
    }

    protected function getRecordType(): string
    {
        return 'page';
    }

    protected function getRecordIdentifier(Content|Page $record): string
    {
        /** @var Page $record */
        return $record->url;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleCustomAction(array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        return match ($data['action_type']) {
            'copy_new' => $this->handleCopyToNewPage($data, $service),
            'copy_existing' => $this->handleCopyToExistingPage($data, $service),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $importData
     * @param  array<string, mixed>  $data
     */
    protected function processImport(array $importData, array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        /** @var Page $record */
        $record = $this->getRecord();
        $replaceContent = ($data['copy_mode'] ?? 'append') === 'replace';
        $service->copyBlocksFromDataToExisting($importData, $record, $replaceContent);

        $this->showSuccessNotification(
            __('fsp::lang.messages.blocks_imported_to_name', [
                'name' => $record->name,
            ])
        );

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleCopyToNewPage(array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        /** @var Page $record */
        $record = $this->getRecord();
        $targetSite = $this->getTargetSite($data);

        if (! $targetSite) {
            $this->showErrorNotification(__('fsp::lang.messages.target_site_not_found'));

            return null;
        }

        $publish = $data['publish'] ?? false;

        $exportData = $service->exportBlocks($record);

        $page = $service->importAsPage($exportData, $targetSite, $publish);

        $this->showSuccessNotification(
            __('fsp::lang.messages.page_copied_to_site', [
                'name' => $page->name,
                'site' => $targetSite->name,
            ])
        );

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleCopyToExistingPage(array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        /** @var Page $record */
        $record = $this->getRecord();
        /** @var ?Page $targetPage */
        $targetPage = Page::find($data['target_page']);

        if (! $targetPage) {
            $this->showErrorNotification(__('fsp::lang.messages.target_page_not_found'));

            return null;
        }

        if ($record->id === $targetPage->id) {
            $this->showErrorNotification(__('fsp::lang.messages.cannot_copy_to_same_page'));

            return null;
        }

        $replaceContent = ($data['copy_mode'] ?? 'append') === 'replace';
        $publish = $data['publish'] ?? false;

        $service->copyBlocksToExisting($record, $targetPage, $replaceContent, $publish);

        $this->showSuccessNotification(
            __('fsp::lang.messages.blocks_copied_to_page', [
                'name' => $targetPage->name,
            ])
        );

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function getSiteOptions(): array
    {
        return Site::pluck('name', 'id')->toArray();
    }

    /**
     * @return array<int, string>
     */
    protected function getPageOptions(): array
    {
        return Page::pluck('name', 'id')->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getTargetSite(array $data): ?Site
    {
        return Site::find((int) $data['target_site']);
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'pageTransfer');
    }
}
