<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Filament;

use ReflectionMethod;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Filament\Actions\PageTransferAction;
use Zoker\FilamentStaticPages\Models\Page;
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
}
