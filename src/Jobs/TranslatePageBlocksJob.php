<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlockContentTranslator;

/**
 * Translates the text blocks of a page in place. Only the slice starting at
 * $fromIndex is translated, so appended copies do not re-translate (and corrupt)
 * blocks that already exist on the target page in its own language.
 */
class TranslatePageBlocksJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * Safety-net TTL for the uniqueness lock (normally released when the job
     * finishes); must outlive $timeout so a running job keeps its lock.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public int $pageId,
        public string $sourceLocale,
        public string $targetLocale,
        public int $fromIndex = 0,
    ) {}

    /**
     * Deduplicate identical whole-slice translations: a re-dispatched job would
     * re-read the page at run time and feed already-translated text back to the
     * AI declared as the source language, garbling the content. Distinct slices
     * (different fromIndex, e.g. two appends) stay independently queueable.
     */
    public function uniqueId(): string
    {
        return $this->pageId . ':' . $this->targetLocale . ':' . $this->fromIndex;
    }

    public function handle(): void
    {
        // Direction (only OUT of the default site) is enforced at dispatch time;
        // here we just guard the no-op and disabled cases.
        if ($this->sourceLocale === $this->targetLocale || ! config('fsp.ai.enabled')) {
            return;
        }

        $page = Page::allSites()->find($this->pageId);

        if (! $page) {
            return;
        }

        $blocks = $page->content ?? [];

        $head = array_slice($blocks, 0, $this->fromIndex);
        $tail = array_slice($blocks, $this->fromIndex);

        if ($tail === []) {
            return;
        }

        try {
            $translatedTail = app(BlockContentTranslator::class)
                ->translate($tail, $this->sourceLocale, $this->targetLocale);
        } catch (Throwable $e) {
            // Leave the page in the source language rather than retry-storming the provider.
            Log::warning('Page block translation failed', [
                'page_id' => $this->pageId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $page->content = array_merge($head, $translatedTail);

        // saveQuietly: block content does not affect routes/cache, so skip the
        // PageObserver (which runs route:clear) on this background save.
        $page->saveQuietly();
    }
}
