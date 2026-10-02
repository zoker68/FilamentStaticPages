<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Filament;

use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Filament\Actions\PageTransferAction;
use Zoker\FilamentStaticPages\Jobs\TranslatePageBlocksJob;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageTransferActionTest extends TestCase
{
    public function test_target_page_options_list_pages_of_the_chosen_other_site(): void
    {
        // A page on a different site than the one currently in scope.
        $other = Site::factory()->create(['is_active' => true, 'locale' => 'sl']);

        $page = new Page(['name' => 'About', 'url' => 'about', 'layout' => 'app', 'published' => true]);
        $page->setSite($other);
        $page->save();

        $action = PageTransferAction::make();
        $method = new ReflectionMethod($action, 'getPageOptions');
        $method->setAccessible(true);

        /** @var array<int, string> $options */
        $options = $method->invoke($action, $other->id);

        // Regression: the cross-site page must be listed (was hidden by the
        // multisite global scope before the fix, forcing selection of the source).
        expect($options)->toHaveKey($page->id)
            ->and($options[$page->id])->toBe('About');
    }

    public function test_target_page_options_are_empty_without_a_chosen_site(): void
    {
        $action = PageTransferAction::make();
        $method = new ReflectionMethod($action, 'getPageOptions');
        $method->setAccessible(true);

        expect($method->invoke($action, null))->toBe([]);
    }

    public function test_blocks_copied_to_page_message_resolves_its_placeholders(): void
    {
        // The action passes `page` + `site`; the message must use those (regression:
        // it previously used `:page`/`:site` while the code passed `name`).
        $message = __('fsp::lang.messages.blocks_copied_to_page', ['page' => 'Home', 'site' => 'Main site']);

        expect($message)->toContain('Home')
            ->toContain('Main site')
            ->not->toContain(':page')
            ->not->toContain(':site');
    }

    public function test_rewrite_copied_links_localises_internal_links_for_the_target_site(): void
    {
        config(['app.url' => 'https://shop.example']);
        $source = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'domain' => null]);
        $target = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'domain' => null]);

        $page = $this->makeContactPage($target);

        $this->invokeRewrite($page, $source, 0);

        $reloaded = Page::withoutGlobalScope('multisite')->find($page->id);
        expect($reloaded->content[0]['data']['link'])->toBe('/ru/contact');
    }

    public function test_rewrite_copied_links_respects_the_disable_flag(): void
    {
        config(['app.url' => 'https://shop.example', 'fsp.transfer.rewrite_links' => false]);
        $source = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'domain' => null]);
        $target = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'domain' => null]);

        $page = $this->makeContactPage($target);

        $this->invokeRewrite($page, $source, 0);

        $reloaded = Page::withoutGlobalScope('multisite')->find($page->id);
        expect($reloaded->content[0]['data']['link'])->toBe('/contact');
    }

    private function makeContactPage(Site $site): Page
    {
        $page = new Page(['name' => 'Contact', 'url' => 'contact', 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->content = [['type' => 'Banner', 'data' => ['link' => '/contact', 'alt' => 'x']]];
        $page->save();

        return $page;
    }

    private function invokeRewrite(Page $page, Site $sourceSite, int $fromIndex): void
    {
        $action = PageTransferAction::make();
        $method = new ReflectionMethod($action, 'rewriteCopiedLinks');
        $method->setAccessible(true);
        $method->invoke($action, $page, $sourceSite, $fromIndex);
    }

    public function test_translation_is_offered_only_from_the_default_site(): void
    {
        config(['fsp.ai.enabled' => true]);

        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        // Same locale as the default, but NOT the default site — must not offer translation
        // (this is the bug: gating on locale would wrongly treat it as the source).
        $sameLocale = Site::factory()->create(['is_active' => true, 'prefix' => 'us', 'locale' => 'en', 'is_default' => false]);

        expect($this->canTranslate($this->pageOn($default)))->toBeTrue()
            ->and($this->canTranslate($this->pageOn($sameLocale)))->toBeFalse();
    }

    public function test_translation_is_not_dispatched_from_a_non_default_site(): void
    {
        config(['fsp.ai.enabled' => true]);
        Queue::fake();

        // Non-default source with a different target locale still must NOT translate.
        $other = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $this->invokeDispatch($other->id, 'de', 0);

        Queue::assertNotPushed(TranslatePageBlocksJob::class);
    }

    public function test_translation_is_dispatched_from_the_default_site_with_its_locale(): void
    {
        config(['fsp.ai.enabled' => true]);
        Queue::fake();

        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);

        $this->invokeDispatch($default->id, 'de', 2);

        Queue::assertPushed(
            TranslatePageBlocksJob::class,
            fn (TranslatePageBlocksJob $job): bool => $job->sourceLocale === 'en'   // source = default site's locale
                && $job->targetLocale === 'de'
                && $job->fromIndex === 2,
        );
    }

    public function test_translation_is_not_dispatched_when_target_locale_matches_the_default(): void
    {
        config(['fsp.ai.enabled' => true]);
        Queue::fake();

        // Default site (en) → a site that also uses 'en': nothing to translate.
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);

        $this->invokeDispatch($default->id, 'en', 0);

        Queue::assertNotPushed(TranslatePageBlocksJob::class);
    }

    private function canTranslate(Page $record): bool
    {
        $action = PageTransferAction::make()->record($record);
        $method = new ReflectionMethod($action, 'canTranslate');
        $method->setAccessible(true);

        return (bool) $method->invoke($action);
    }

    private function invokeDispatch(?int $sourceSiteId, ?string $targetLocale, int $fromIndex): void
    {
        $action = PageTransferAction::make();
        $method = new ReflectionMethod($action, 'dispatchTranslation');
        $method->setAccessible(true);
        $method->invoke($action, 1, $sourceSiteId, $targetLocale, $fromIndex);
    }

    private function pageOn(Site $site): Page
    {
        $page = new Page(['name' => 'P', 'url' => 'p-' . $site->id, 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->content = [];
        $page->save();

        return $page;
    }

    public function test_copy_from_default_site_links_the_new_page_to_the_original(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $source = $this->pageOn($default);
        $target = $this->pageOn($ru);

        $this->invokeLinkToOriginal($target, $source);

        expect(Page::withoutGlobalScope('multisite')->find($target->id)->original_id)->toBe($source->id);
    }

    public function test_copy_from_a_translation_carries_over_the_original(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);
        $de = Site::factory()->create(['is_active' => true, 'prefix' => 'de', 'locale' => 'de', 'is_default' => false]);

        $original = $this->pageOn($default);
        $ruTranslation = $this->pageOn($ru);
        $ruTranslation->original_id = $original->id;
        $ruTranslation->save();

        $target = $this->pageOn($de);
        $this->invokeLinkToOriginal($target, $ruTranslation);

        expect(Page::withoutGlobalScope('multisite')->find($target->id)->original_id)->toBe($original->id);
    }

    public function test_copy_from_a_non_default_page_without_original_leaves_it_unlinked(): void
    {
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);
        $de = Site::factory()->create(['is_active' => true, 'prefix' => 'de', 'locale' => 'de', 'is_default' => false]);

        $source = $this->pageOn($ru);
        $target = $this->pageOn($de);

        $this->invokeLinkToOriginal($target, $source);

        expect(Page::withoutGlobalScope('multisite')->find($target->id)->original_id)->toBeNull();
    }

    public function test_copy_to_new_page_handler_links_the_copy_to_its_original(): void
    {
        config(['fsp.ai.enabled' => false]);
        Queue::fake();

        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $source = $this->pageOn($default);

        // Drive the real handler (not just linkToOriginal) so the wiring is covered.
        $action = PageTransferAction::make()->record($source);
        $method = new ReflectionMethod($action, 'handleCopyToNewPage');
        $method->setAccessible(true);
        $method->invoke($action, ['action_type' => 'copy_new', 'target_site' => $ru->id, 'publish' => false], new BlocksExportImportService);

        $copied = Page::withoutGlobalScope('multisite')->where('site_id', $ru->id)->first();

        expect($copied)->not->toBeNull()
            ->and($copied->original_id)->toBe($source->id);
    }

    public function test_a_copy_landing_on_the_default_site_is_never_linked(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $source = $this->pageOn($ru);
        $target = $this->pageOn($default); // lands on the default site → stays an original

        $this->invokeLinkToOriginal($target, $source);

        expect(Page::withoutGlobalScope('multisite')->find($target->id)->original_id)->toBeNull();
    }

    private function invokeLinkToOriginal(Page $target, Page $source): void
    {
        $action = PageTransferAction::make();
        $method = new ReflectionMethod($action, 'linkToOriginal');
        $method->setAccessible(true);
        $method->invoke($action, $target, $source);
    }
}
