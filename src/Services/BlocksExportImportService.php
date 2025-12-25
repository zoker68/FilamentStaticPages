<?php

namespace Zoker\FilamentStaticPages\Services;

use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Enums\ContentType;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Models\Page;

class BlocksExportImportService
{
    public const string VERSION = '1.0';

    /**
     * @return array<string, mixed>
     */
    public function exportBlocks(Page|Content $record, ?string $locale = null): array
    {
        $type = $record instanceof Page ? ContentType::Page : ContentType::Content;

        $blocks = $record instanceof Content && $locale
            ? $record->getTranslation('content', $locale, false)
            : $record->content;

        $data = [
            'version' => self::VERSION,
            'type' => $type->value,
            'exported_at' => now()->toIso8601String(),
            'locale' => $locale,
            'blocks' => $blocks ?? [],
        ];

        if ($record instanceof Page) {
            $data['source_site_id'] = $record->site_id;
            $data['meta'] = [
                'name' => $record->name,
                'url' => $record->url,
                'layout' => $record->layout,
                'published' => $record->published,
            ];
        } else {
            $data['meta'] = [
                'code' => $record->code,
            ];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importAsPage(array $data, Site $targetSite, bool $publish = false): Page
    {
        $page = new Page;
        $page->site_id = $targetSite->id;

        if (isset($data['meta']['name'])) {
            $page->name = $data['meta']['name'];
            $page->url = $this->generateUniqueUrl($data['meta']['url'] ?? 'imported', $targetSite);
        } else {
            $page->name = $data['meta']['code'] ?? 'Imported';
            $page->url = $this->generateUniqueUrl($data['meta']['code'] ?? 'imported', $targetSite);
        }

        $page->layout = $data['meta']['layout'] ?? config('fsp.layout');
        $page->published = $publish ? ($data['meta']['published'] ?? false) : false;
        $page->content = $data['blocks'] ?? [];
        $page->save();

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importAsContent(array $data, ?string $targetLocale = null): Content
    {
        $content = new Content;

        if (isset($data['meta']['code'])) {
            $content->code = $this->generateUniqueCode($data['meta']['code']);
        } else {
            $content->code = $this->generateUniqueCode($data['meta']['url'] ?? 'imported');
        }

        $locale = $targetLocale ?? $data['locale'] ?? config('app.locale');
        $content->setTranslation('content', $locale, $data['blocks'] ?? []);
        $content->save();

        return $content;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importBlocksToExistingContent(array $data, Content $target, ?string $targetLocale = null, bool $replaceContent = true): Content
    {
        $locale = $targetLocale ?? $data['locale'] ?? FilamentSiteManager::getCurrentSite()->locale;
        $blocks = $data['blocks'] ?? [];

        if ($replaceContent) {
            $target->setTranslation('content', $locale, $blocks);
        } else {
            $existingContent = $target->getTranslation('content', $locale, false) ?? [];
            $target->setTranslation('content', $locale, array_merge($existingContent, $blocks));
        }

        $target->save();

        return $target;
    }

    public function copyBlocksToExisting(Page|Content $source, Page|Content $target, bool $replaceContent = true, ?string $sourceLocale = null, ?string $targetLocale = null): Page|Content
    {
        $blocks = $source instanceof Content && $sourceLocale
            ? $source->getTranslation('content', $sourceLocale, false) ?? []
            : $source->content ?? [];

        return $this->copyBlocksToExistingProcess($target, $targetLocale, $replaceContent, $blocks);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function copyBlocksFromDataToExisting(array $data, Page|Content $target, bool $replaceContent = true, ?string $targetLocale = null): Page|Content
    {
        $blocks = $data['blocks'] ?? [];

        return $this->copyBlocksToExistingProcess($target, $targetLocale, $replaceContent, $blocks);
    }

    public function copyBlocksToExistingProcess(Content|Page $target, ?string $targetLocale, bool $replaceContent, mixed $blocks): Page|Content
    {
        if ($target instanceof Content && $targetLocale) {
            if ($replaceContent) {
                $target->setTranslation('content', $targetLocale, $blocks);
            } else {
                $existingContent = $target->getTranslation('content', $targetLocale, false) ?? [];
                $target->setTranslation('content', $targetLocale, array_merge($existingContent, $blocks));
            }
        } else {
            if ($replaceContent) {
                $target->content = $blocks;
            } else {
                $existingContent = $target->content ?? [];
                $target->content = array_merge($existingContent, $blocks);
            }
        }

        $target->save();

        return $target;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateImportData(array $data): bool
    {
        if (! isset($data['version'], $data['blocks'])) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function getImportType(array $data): ?ContentType
    {
        $type = $data['type'] ?? null;

        return $type ? ContentType::tryFrom($type) : null;
    }

    protected function generateUniqueUrl(string $baseUrl, Site $targetSite): string
    {
        $url = $baseUrl;
        $counter = 1;

        while ($this->urlExistsForSite($url, $targetSite)) {
            $url = $baseUrl . '-' . $counter;
            $counter++;
        }

        return $url;
    }

    protected function urlExistsForSite(string $url, Site $targetSite): bool
    {
        return Page::withoutGlobalScope('multisite')
            ->where('site_id', $targetSite->id)
            ->where('url', $url)
            ->exists();
    }

    protected function generateUniqueCode(string $baseCode): string
    {
        $code = $baseCode;
        $counter = 1;

        while (Content::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $counter;
            $counter++;
        }

        return $code;
    }

    public function exportToJson(Page|Content $record, ?string $locale = null): string|false
    {
        return json_encode($this->exportBlocks($record, $locale), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseJsonImport(string $json): ?array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (! $this->validateImportData($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @deprecated Use importAsPage() instead
     */
    public function importPage(array $data, Site $targetSite, bool $publish = false): Page
    {
        return $this->importAsPage($data, $targetSite, $publish);
    }

    /**
     * @deprecated Use copyBlocksToExisting() instead
     */
    public function copyPageToSite(Page $sourcePage, Site $targetSite, bool $publish = false): Page
    {
        $exportData = $this->exportBlocks($sourcePage);

        return $this->importAsPage($exportData, $targetSite, $publish);
    }
}
