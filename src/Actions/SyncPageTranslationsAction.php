<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Actions;

use Zoker\FilamentStaticPages\Jobs\TranslatePageBlocksJob;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlockLinkRewriter;

/**
 * Pushes an original page's content into all of its linked translations and
 * re-translates them. Full re-sync (not an incremental block diff): the block
 * JSON has no stable per-block identity, so the whole content is replaced and
 * re-translated. Slug/name/published of each translation are left untouched.
 */
class SyncPageTranslationsAction
{
    public function __construct(
        private readonly BlockLinkRewriter $linkRewriter,
    ) {}

    /**
     * Sync the original into every linked translation.
     */
    public function handle(Page $original): SyncPageTranslationsResult
    {
        if (! self::eligible($original)) {
            return new SyncPageTranslationsResult(0, 0);
        }

        $sourceSite = $original->site;
        $sourceLocale = $sourceSite?->locale;
        $rewriteLinks = (bool) config('fsp.transfer.rewrite_links', true);
        $aiEnabled = (bool) config('fsp.ai.enabled');

        $synced = 0;
        $queued = 0;

        /** @var Page $translation */
        foreach ($original->translations()->with('site')->get() as $translation) {
            // Replace the translation's content with the original's blocks (still
            // in the source language at this point). Saved quietly: a content-only
            // change needs no route-cache bust, and the observer would otherwise
            // run route:clear once per translation (mirrors BlockLinkRewriter).
            $translation->content = $original->content ?? [];
            $translation->saveQuietly();

            // Localise internal links to the translation's own site (whole content).
            if ($rewriteLinks && $sourceSite !== null) {
                $this->linkRewriter->rewritePage($translation, $sourceSite, 0);
            }

            // Queue translation of the whole content into the translation's locale.
            $targetLocale = $translation->site?->locale;
            if ($aiEnabled && $sourceLocale !== null && $targetLocale !== null && $targetLocale !== $sourceLocale) {
                TranslatePageBlocksJob::dispatch($translation->id, $sourceLocale, $targetLocale, 0);
                $queued++;
            }

            $synced++;
        }

        return new SyncPageTranslationsResult($synced, $queued);
    }

    /**
     * A page may be synced only when it is an original on the default site (never
     * a translation) — translations flow out of the default site. The copy +
     * link-rewrite steps need no AI, so fsp.ai.enabled does NOT gate the sync
     * itself — it only gates the per-translation job dispatch in handle()
     * (mirrors how PageTransferAction copies without AI and gates only translate).
     */
    public static function eligible(?Page $original): bool
    {
        return $original !== null
            && $original->site?->is_default === true
            && $original->original_id === null;
    }
}
