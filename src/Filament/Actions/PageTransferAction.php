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
use Zoker\FilamentStaticPages\Services\BlockLinkRewriter;
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

        $this->linkToOriginal($page, $record);
        $this->rewriteCopiedLinks($page, $record->site, 0);

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
        $targetPage = Page::allSites()->find($data['target_page']);

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

        $service->copyBlocksToExisting($record, $targetPage, $replaceContent);

        // The 'Publish after copy' toggle must be applied explicitly here — the
        // block-copy service only touches content, never the published flag.
        if ($publish && ! $targetPage->published) {
            $targetPage->published = true;
            $targetPage->save();
        }

        $this->rewriteCopiedLinks($targetPage, $record->site, $fromIndex);

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

        return Page::forSite($siteId)
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
     * source page lives on the default (original) site — translations flow out
     * of that site, not out of a locale (a locale may repeat across sites).
     */
    protected function canTranslate(): bool
    {
        if (! config('fsp.ai.enabled')) {
            return false;
        }

        /** @var ?Page $record */
        $record = $this->getRecord();

        return $record?->site?->is_default === true;
    }

    protected function localeForSiteId(?int $siteId): ?string
    {
        return $siteId ? Site::find($siteId)?->locale : null;
    }

    /**
     * Rewrite internal links in the just-copied blocks so they point at the
     * target site (host + locale prefix). Only the tail from $fromIndex is
     * touched, keeping an existing target page's own blocks intact on append.
     */
    /**
     * Link the freshly copied page to its original (for hreflang). Copying out of
     * the default site → the source IS the original; copying a translation → carry
     * over its original. Otherwise there's no known original to link.
     */
    protected function linkToOriginal(Page $target, Page $source): void
    {
        // A page on the default site IS an original — it never points at one (and two
        // group members on one site would collide in AlternateLinks, keyed by site).
        if ($target->site?->is_default) {
            return;
        }

        $originalId = $source->original_id ?? ($source->site?->is_default ? $source->id : null);

        if ($originalId !== null) {
            $target->original_id = $originalId;
            $target->save();
        }
    }

    protected function rewriteCopiedLinks(Page $target, ?Site $sourceSite, int $fromIndex): void
    {
        if (! config('fsp.transfer.rewrite_links', true) || $sourceSite === null) {
            return;
        }

        app(BlockLinkRewriter::class)->rewritePage($target, $sourceSite, $fromIndex);
    }

    /**
     * Queue translation of the just-copied block tail into the target locale,
     * but only when copying OUT of the default (original) site and into a
     * different locale.
     */
    protected function dispatchTranslation(int $pageId, ?int $sourceSiteId, ?string $targetLocale, int $fromIndex): void
    {
        if (! config('fsp.ai.enabled')) {
            return;
        }

        $sourceSite = $sourceSiteId ? Site::find($sourceSiteId) : null;

        if ($sourceSite === null || ! $sourceSite->is_default) {
            return;
        }

        $sourceLocale = $sourceSite->locale;

        if ($targetLocale === null) {
            return;
        }

        // Same locale on both sites (locales may repeat): nothing to translate —
        // tell the user so a ticked "Translate content" doesn't look stuck.
        if ($sourceLocale === $targetLocale) {
            $this->showInfoNotification(__('fsp::lang.messages.translation_same_locale'));

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
