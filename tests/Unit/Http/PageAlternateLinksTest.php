<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Http;

use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentStaticPages\Http\Controllers\PageController;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageAlternateLinksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AlternateLinks::clear();

        // Minimal named routes so multisite_route() can resolve.
        Route::get('/', fn () => '')->name('index');
        Route::get('/{page}', fn () => '')->name('fsp.page');
    }

    public function test_it_builds_hreflang_from_the_group_despite_different_slugs(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, 'contact');
        $translation = $this->makePage($ru, 'kontakt', $original->id); // different slug — slug-matching would miss it

        $this->invokeSetAlternateLinks($translation);

        $links = AlternateLinks::get();

        $this->assertCount(2, $links);
        $this->assertTrue($links[$default->id]['isDefault']);   // → x-default in the head
        $this->assertFalse($links[$ru->id]['isDefault']);

        // Each version uses its OWN localized slug (would be identical under slug-matching).
        $this->assertStringContainsString('contact', $links[$default->id]['url']);
        $this->assertStringContainsString('kontakt', $links[$ru->id]['url']);
    }

    public function test_unpublished_versions_are_excluded(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, 'contact');
        $this->makePage($ru, 'kontakt', $original->id, published: false);

        $this->invokeSetAlternateLinks($original);

        $this->assertCount(1, AlternateLinks::get());
    }

    public function test_versions_from_another_group_are_excluded(): void
    {
        $default = Site::factory()->create(['site_group_id' => 1, 'is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['site_group_id' => 1, 'is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);
        // Same original_id but a site in a DIFFERENT group — must NOT leak into hreflang.
        $other = Site::factory()->create(['site_group_id' => 2, 'is_active' => true, 'prefix' => 'de', 'locale' => 'de', 'is_default' => true]);

        $original = $this->makePage($default, 'contact');
        $this->makePage($ru, 'kontakt', $original->id);
        $this->makePage($other, 'kontakt-de', $original->id);

        $this->invokeSetAlternateLinks($original);

        $links = AlternateLinks::get();

        $this->assertCount(2, $links);
        $this->assertArrayHasKey($default->id, $links);
        $this->assertArrayHasKey($ru->id, $links);
        $this->assertArrayNotHasKey($other->id, $links);
    }

    private function invokeSetAlternateLinks(Page $page): void
    {
        $controller = new PageController;
        $method = new ReflectionMethod($controller, 'setAlternateLinks');
        $method->setAccessible(true);
        $method->invoke($controller, $page);
    }

    private function makePage(Site $site, string $url, ?int $originalId = null, bool $published = true): Page
    {
        $page = new Page(['name' => 'P-' . $url, 'url' => $url, 'layout' => 'app', 'published' => $published, 'original_id' => $originalId]);
        $page->setSite($site);
        $page->save();

        return $page;
    }
}
