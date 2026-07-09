<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Filament;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Actions\SyncPageTranslationsAction;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class SyncTranslationsActionTest extends TestCase
{
    private function pageOn(Site $site, ?int $originalId = null): Page
    {
        $page = new Page([
            'name' => 'P',
            'url' => 'p-' . uniqid(),
            'layout' => 'app',
            'published' => true,
            'original_id' => $originalId,
        ]);
        $page->setSite($site);
        $page->save();

        return $page;
    }

    public function test_eligible_only_for_a_default_site_original(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ruSite = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->pageOn($default);

        expect(SyncPageTranslationsAction::eligible($original))->toBeTrue()
            // a translation (original_id set) is never an origin
            ->and(SyncPageTranslationsAction::eligible($this->pageOn($ruSite, originalId: $original->id)))->toBeFalse()
            // non-default site
            ->and(SyncPageTranslationsAction::eligible($this->pageOn($ruSite)))->toBeFalse()
            // null record
            ->and(SyncPageTranslationsAction::eligible(null))->toBeFalse();
    }

    public function test_eligible_even_when_ai_is_disabled(): void
    {
        // The copy + link-rewrite path needs no AI; only the translation job
        // dispatch is gated by fsp.ai.enabled (inside handle()).
        config(['fsp.ai.enabled' => false]);

        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);

        expect(SyncPageTranslationsAction::eligible($this->pageOn($default)))->toBeTrue();
    }

    public function test_queued_message_resolves_its_placeholder(): void
    {
        $message = __('fsp::lang.messages.sync_translations_queued', ['count' => 3]);

        expect($message)->toContain('3')->not->toContain(':count');
    }
}
