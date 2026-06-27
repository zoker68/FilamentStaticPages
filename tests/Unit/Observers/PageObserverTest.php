<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Observers;

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageObserverTest extends TestCase
{
    private function savePage(Site $site): Page
    {
        $page = new Page(['name' => 'P', 'url' => 'p', 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->save();

        return $page;
    }

    private function primeCaches(): void
    {
        cache()->put(Page::CACHE_KEY_ROUTES, ['x']);
        cache()->put(Page::CACHE_KEY_ALLOWED_URLS, ['x']);
    }

    private function assertCachesCleared(): void
    {
        $this->assertFalse(cache()->has(Page::CACHE_KEY_ROUTES));
        $this->assertFalse(cache()->has(Page::CACHE_KEY_ALLOWED_URLS));
    }

    public function test_saving_a_page_clears_the_route_caches(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);
        $this->primeCaches();

        $this->savePage($site);

        $this->assertCachesCleared();
    }

    public function test_deleting_a_page_clears_the_route_caches(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);
        $page = $this->savePage($site);

        $this->primeCaches();
        $page->delete();

        $this->assertCachesCleared();
    }
}
