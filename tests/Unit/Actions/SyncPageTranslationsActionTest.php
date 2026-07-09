<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Actions;

use Illuminate\Support\Facades\Queue;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Actions\SyncPageTranslationsAction;
use Zoker\FilamentStaticPages\Actions\SyncPageTranslationsResult;
use Zoker\FilamentStaticPages\Jobs\TranslatePageBlocksJob;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class SyncPageTranslationsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['fsp.ai.enabled' => true]);
        Queue::fake();
    }

    private function handle(Page $original): SyncPageTranslationsResult
    {
        return app(SyncPageTranslationsAction::class)->handle($original);
    }

    /**
     * @param  array<int, array<string, mixed>>  $content
     */
    private function makePage(Site $site, array $content, ?int $originalId = null): Page
    {
        $page = new Page([
            'name' => 'P',
            'url' => 'p-' . uniqid(),
            'layout' => 'app',
            'published' => true,
            'original_id' => $originalId,
        ]);
        $page->setSite($site);
        $page->content = $content;
        $page->save();

        return $page;
    }

    private function reload(int $id): Page
    {
        return Page::withoutGlobalScope('multisite')->findOrFail($id);
    }

    public function test_it_syncs_content_into_all_translations_and_queues_one_job_per_locale(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);
        $slSite = Site::factory()->create(['is_active' => true, 'prefix' => 'sl', 'locale' => 'sl', 'is_default' => false]);

        $original = $this->makePage($default, [['type' => 'Heading', 'data' => ['heading' => 'Fresh']]]);
        $ru = $this->makePage($ruSite, [['type' => 'Heading', 'data' => ['heading' => 'Stale RU']]], originalId: $original->id);
        $sl = $this->makePage($slSite, [['type' => 'Heading', 'data' => ['heading' => 'Stale SL']]], originalId: $original->id);

        $result = $this->handle($original);

        expect($result->synced)->toBe(2)
            ->and($result->queued)->toBe(2)
            // Content pushed (still in the source language — the job runs later).
            ->and($this->reload($ru->id)->content[0]['data']['heading'])->toBe('Fresh')
            ->and($this->reload($sl->id)->content[0]['data']['heading'])->toBe('Fresh');

        Queue::assertPushed(TranslatePageBlocksJob::class, 2);
        Queue::assertPushed(TranslatePageBlocksJob::class, fn (TranslatePageBlocksJob $job): bool => $job->pageId === $ru->id && $job->sourceLocale === 'en' && $job->targetLocale === 'ru' && $job->fromIndex === 0);
        Queue::assertPushed(TranslatePageBlocksJob::class, fn (TranslatePageBlocksJob $job): bool => $job->pageId === $sl->id && $job->sourceLocale === 'en' && $job->targetLocale === 'sl' && $job->fromIndex === 0);
    }

    public function test_a_repeated_sync_does_not_queue_a_duplicate_translation_job(): void
    {
        // Regression: without a uniqueness guard the second job would re-read the
        // page after the first one translated it and feed already-translated text
        // back to the AI declared as the source language.
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, [['type' => 'Heading', 'data' => ['heading' => 'Fresh']]]);
        $this->makePage($ruSite, [], originalId: $original->id);

        $this->handle($original);
        $this->handle($original); // double-submit / sync while the queue is backed up

        Queue::assertPushed(TranslatePageBlocksJob::class, 1);
    }

    public function test_it_does_not_queue_a_job_for_a_same_locale_translation_but_still_copies(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $sameLocale = Site::factory()->create(['is_active' => true, 'prefix' => 'us', 'locale' => 'en', 'is_default' => false]);

        $original = $this->makePage($default, [['type' => 'Heading', 'data' => ['heading' => 'Fresh']]]);
        $us = $this->makePage($sameLocale, [['type' => 'Heading', 'data' => ['heading' => 'Stale']]], originalId: $original->id);

        $result = $this->handle($original);

        expect($result->synced)->toBe(1)
            ->and($result->queued)->toBe(0)
            ->and($this->reload($us->id)->content[0]['data']['heading'])->toBe('Fresh');

        Queue::assertNothingPushed();
    }

    public function test_it_rewrites_internal_links_in_the_copied_content(): void
    {
        config(['app.url' => 'https://example.com']);

        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true, 'domain' => null]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false, 'domain' => null]);

        $original = $this->makePage($default, [['type' => 'Banner', 'data' => ['link' => '/contact', 'alt' => 'x']]]);
        $ru = $this->makePage($ruSite, [], originalId: $original->id);

        $this->handle($original);

        expect($this->reload($ru->id)->content[0]['data']['link'])->toBe('/ru/contact');
    }

    public function test_it_leaves_translation_slug_name_and_published_untouched(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, [['type' => 'Heading', 'data' => ['heading' => 'Fresh']]]);

        $ru = new Page([
            'name' => 'Ru name',
            'url' => 'ru-slug',
            'layout' => 'app',
            'published' => false,
            'original_id' => $original->id,
        ]);
        $ru->setSite($ruSite);
        $ru->content = [];
        $ru->save();

        $this->handle($original);

        $reloaded = $this->reload($ru->id);
        expect($reloaded->name)->toBe('Ru name')
            ->and($reloaded->url)->toBe('ru-slug')
            ->and($reloaded->published)->toBeFalse();
    }

    public function test_it_does_nothing_when_the_page_is_not_on_the_default_site(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        // A non-default page that (incorrectly) has a translation linked to it.
        $notOriginal = $this->makePage($ruSite, [['type' => 'Heading', 'data' => ['heading' => 'X']]]);
        $this->makePage($default, [], originalId: $notOriginal->id);

        expect($this->handle($notOriginal)->synced)->toBe(0);
        Queue::assertNothingPushed();
    }

    public function test_it_copies_without_queueing_when_ai_is_disabled(): void
    {
        // The copy + link-rewrite path needs no AI — only the translation job is
        // gated (regression: eligible() used to bail out entirely, silently
        // breaking sync to same-locale mirror sites on non-AI installs).
        config(['fsp.ai.enabled' => false]);

        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, [['type' => 'Heading', 'data' => ['heading' => 'Fresh']]]);
        $ru = $this->makePage($ruSite, [['type' => 'Heading', 'data' => ['heading' => 'Stale']]], originalId: $original->id);

        $result = $this->handle($original);

        expect($result->synced)->toBe(1)
            ->and($result->queued)->toBe(0)
            ->and($this->reload($ru->id)->content[0]['data']['heading'])->toBe('Fresh');
        Queue::assertNothingPushed();
    }

    public function test_it_does_nothing_for_a_translation_page(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, [['type' => 'Heading', 'data' => ['heading' => 'Fresh']]]);
        // A translation (original_id set) is never itself an origin.
        $ru = $this->makePage($ruSite, [['type' => 'Heading', 'data' => ['heading' => 'Stale']]], originalId: $original->id);

        expect($this->handle($ru)->synced)->toBe(0);
        Queue::assertNothingPushed();
    }
}
