<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Models;

use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageTest extends TestCase
{
    private function makePage(Site $site, array $attributes = []): Page
    {
        // site_id / content are not mass-assignable on Page, so set them explicitly.
        $page = new Page(array_merge([
            'name' => 'Test Page',
            'url' => 'test',
            'layout' => 'app',
            'published' => true,
        ], $attributes));
        $page->setSite($site);
        $page->save();

        return $page;
    }

    public function test_it_creates_a_page_scoped_to_a_site(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);

        $page = $this->makePage($site, ['url' => 'about']);

        $this->assertDatabaseHas($page->getTable(), [
            'url' => 'about',
            'site_id' => $site->id,
        ]);
    }

    public function test_global_scope_filters_pages_by_current_site(): void
    {
        $site1 = Site::factory()->create(['is_active' => true]);
        $site2 = Site::factory()->create(['is_active' => true]);

        $this->makePage($site1, ['url' => 'one']);
        $this->makePage($site2, ['url' => 'two']);

        SiteManager::setCurrentSite($site1);

        $pages = Page::all();
        $this->assertCount(1, $pages);
        $this->assertEquals('one', $pages->first()->url);
    }

    public function test_published_scope(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);

        $published = $this->makePage($site, ['url' => 'pub', 'published' => true]);
        $draft = $this->makePage($site, ['url' => 'draft', 'published' => false]);

        $result = Page::published()->get();

        $this->assertTrue($result->contains($published));
        $this->assertFalse($result->contains($draft));
    }

    public function test_url_scope_matches_and_handles_empty_url(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);

        $about = $this->makePage($site, ['url' => 'about']);
        $home = $this->makePage($site, ['url' => null]);

        $this->assertTrue(Page::url('about')->get()->contains($about));
        $this->assertTrue(Page::url(null)->get()->contains($home));
        $this->assertFalse(Page::url(null)->get()->contains($about));

        // The add_parent_id_field migration's down() restores url to NOT NULL, which would
        // choke on this null-url row during the test-suite migration rollback.
        $home->delete();
    }

    public function test_parent_relationship(): void
    {
        $site = Site::factory()->create(['is_active' => true]);
        SiteManager::setCurrentSite($site);

        $parent = $this->makePage($site, ['url' => 'parent']);
        $child = $this->makePage($site, ['url' => 'child']);
        $child->parent_id = $parent->id;
        $child->save();

        $this->assertTrue($parent->is($child->fresh()->parent));
    }
}
