<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Filament;

use ReflectionMethod;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Filament\Resources\PageResource\PageResource;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageResourcePickerTest extends TestCase
{
    public function test_original_picker_is_hidden_on_the_default_site_and_shown_elsewhere(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $method = new ReflectionMethod(PageResource::class, 'showsOriginalPicker');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null, $this->pageOn($default)));
        $this->assertTrue($method->invoke(null, $this->pageOn($ru)));
    }

    public function test_original_picker_options_are_only_default_site_pages(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->pageOn($default);
        $ruPage = $this->pageOn($ru);

        $method = new ReflectionMethod(PageResource::class, 'originalPageOptions');
        $method->setAccessible(true);

        /** @var array<int, string> $options */
        $options = $method->invoke(null);

        $this->assertArrayHasKey($original->id, $options);
        $this->assertArrayNotHasKey($ruPage->id, $options);
    }

    private function pageOn(Site $site): Page
    {
        $page = new Page(['name' => 'P', 'url' => 'p-' . $site->id, 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->save();

        return $page;
    }
}
