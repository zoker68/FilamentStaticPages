<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Models;

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageRouteCacheTest extends TestCase
{
    private function publishPage(Site $site, string $url): Page
    {
        $page = new Page(['name' => 'P', 'url' => $url, 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->save();

        return $page;
    }

    public function test_an_empty_url_list_is_returned_but_not_cached(): void
    {
        $this->assertSame([], Page::getAllowedUrls());
        $this->assertSame([], Page::getAllRoutes());

        $this->assertFalse(cache()->has(Page::CACHE_KEY_ALLOWED_URLS));
        $this->assertFalse(cache()->has(Page::CACHE_KEY_ROUTES));
    }

    public function test_pages_created_after_an_empty_read_are_served_without_a_cache_flush(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);

        $this->assertSame([], Page::getAllowedUrls());

        Page::withoutEvents(fn () => $this->publishPage($site, 'about'));

        $this->assertSame(['about'], Page::getAllowedUrls());
        $this->assertCount(1, Page::getAllRoutes());
    }

    public function test_an_empty_list_left_by_an_older_version_is_replaced(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);
        Page::withoutEvents(fn () => $this->publishPage($site, 'about'));

        cache()->forever(Page::CACHE_KEY_ALLOWED_URLS, []);

        $this->assertSame(['about'], Page::getAllowedUrls());
        $this->assertSame(['about'], cache()->get(Page::CACHE_KEY_ALLOWED_URLS));
    }

    public function test_a_non_empty_list_is_cached_forever(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);
        $this->publishPage($site, 'about');

        $this->assertSame(['about'], Page::getAllowedUrls());
        $this->assertTrue(cache()->has(Page::CACHE_KEY_ALLOWED_URLS));

        Page::withoutEvents(fn () => $this->publishPage($site, 'contact'));

        $this->assertSame(['about'], Page::getAllowedUrls());
    }
}
