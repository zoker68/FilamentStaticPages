<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Jobs\TranslatePageBlocksJob;
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
                ->live()
                ->required(fn ($get) => in_array($get('action_type'), ['copy_new', 'copy_existing']))
                ->hidden(fn ($get) => in_array($get('action_type'), ['export', 'import'])),

            Select::make('target_page')
                ->label(__('fsp::lang.form.target_page'))
                ->options(fn ($get) => $this->getPageOptions($get('target_site')))
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

            Toggle::make('translate')
                ->label(__('fsp::lang.form.translate_content'))
                ->helperText(__('fsp::lang.form.translate_content_hint'))
                ->default(false)
                ->visible(fn ($get): bool => $this->canTranslate() && in_array($get('action_type'), ['copy_new', 'copy_existing'])),
        ];
    }

    protected function getRecordType(): string
    {
        return 'page';
    }

    protected function getRecordIdentifier(Content|Page $record): string
    {
        /** @var Page $record */
        return $record->url ?? Str::slug($record->name);
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

        if ($data['translate'] ?? false) {
            $this->dispatchTranslation($page->id, $record->site_id, $targetSite->locale, 0);
        }

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
        $targetPage = Page::withoutGlobalScope('multisite')->find($data['target_page']);

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

        // Append merges source blocks at the end, so only that tail must be
        // translated — never the target's existing, already-localised blocks.
        $fromIndex = $replaceContent ? 0 : count($targetPage->content ?? []);

        $service->copyBlocksToExisting($record, $targetPage, $replaceContent, $publish);

        $this->showSuccessNotification(
            __('fsp::lang.messages.blocks_copied_to_page', [
                'page' => $targetPage->name,
                'site' => Site::find($targetPage->site_id)?->name ?? '',
            ])
        );

        if ($data['translate'] ?? false) {
            $this->dispatchTranslation($targetPage->id, $record->site_id, $this->localeForSiteId($targetPage->site_id), $fromIndex);
        }

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
     * Pages of the chosen target site (cross-site, so the multisite global scope
     * must be bypassed — otherwise only the current site's pages are listed and
     * copying to another site's page is impossible).
     *
     * @return array<int, string>
     */
    protected function getPageOptions(int|string|null $siteId): array
    {
        $siteId = (int) $siteId;

        if ($siteId === 0) {
            return [];
        }

        return Page::withoutGlobalScope('multisite')
            ->where('site_id', $siteId)
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getTargetSite(array $data): ?Site
    {
        return Site::find((int) $data['target_site']);
    }

    /**
     * Translation is offered only when an AI translator is available and the
     * source page is in the single main language (translations flow out of it).
     */
    protected function canTranslate(): bool
    {
        if (! config('fsp.ai.enabled')) {
            return false;
        }

        /** @var ?Page $record */
        $record = $this->getRecord();
        $sourceLocale = $this->localeForSiteId($record?->site_id);

        return $sourceLocale !== null
            && $sourceLocale === config('fsp.ai.base_locale');
    }

    protected function localeForSiteId(?int $siteId): ?string
    {
        return $siteId ? Site::find($siteId)?->locale : null;
    }

    /**
     * Queue translation of the just-copied block tail into the target locale,
     * but only out of the main language and into a different one.
     */
    protected function dispatchTranslation(int $pageId, ?int $sourceSiteId, ?string $targetLocale, int $fromIndex): void
    {
        if (! config('fsp.ai.enabled')) {
            return;
        }

        $sourceLocale = $this->localeForSiteId($sourceSiteId);

        if ($sourceLocale === null || $targetLocale === null) {
            return;
        }

        if ($sourceLocale !== config('fsp.ai.base_locale') || $sourceLocale === $targetLocale) {
            return;
        }

        TranslatePageBlocksJob::dispatch($pageId, $sourceLocale, $targetLocale, $fromIndex);

        $this->showInfoNotification(
            __('fsp::lang.messages.translation_queued', ['locale' => strtoupper($targetLocale)])
        );
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'pageTransfer');
    }
}
