<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Models;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;

class PageTranslationGroupTest extends TestCase
{
    public function test_it_returns_all_versions_across_sites_even_with_different_slugs(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, 'contact');
        $translation = $this->makePage($ru, 'kontakt', $original->id); // deliberately different slug

        // From the translation and from the original the group is the same pair,
        // resolved via original_id — NOT by matching slugs.
        $this->assertEqualsCanonicalizing(
            [$original->id, $translation->id],
            $translation->translationGroup()->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$original->id, $translation->id],
            $original->translationGroup()->pluck('id')->all()
        );
    }

    public function test_it_excludes_pages_from_other_groups(): void
    {
        $default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'is_default' => true]);
        $ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'is_default' => false]);

        $original = $this->makePage($default, 'contact');
        $translation = $this->makePage($ru, 'kontakt', $original->id);

        // A completely separate group must NOT leak into this one.
        $otherOriginal = $this->makePage($default, 'about');
        $otherTranslation = $this->makePage($ru, 'ueber', $otherOriginal->id);

        $groupIds = $translation->translationGroup()->pluck('id')->all();

        expect($groupIds)->not->toContain($otherOriginal->id)
            ->and($groupIds)->not->toContain($otherTranslation->id)
            ->and($groupIds)->toContain($original->id)
            ->and($groupIds)->toContain($translation->id);
    }

    private function makePage(Site $site, string $url, ?int $originalId = null): Page
    {
        $page = new Page(['name' => 'P-' . $url, 'url' => $url, 'layout' => 'app', 'published' => true, 'original_id' => $originalId]);
        $page->setSite($site);
        $page->save();

        return $page;
    }
}
