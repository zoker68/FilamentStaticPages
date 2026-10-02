<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Services;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Services\LinkRewriter;
use Zoker\FilamentStaticPages\Tests\TestCase;

class LinkRewriterTest extends TestCase
{
    private Site $default;

    private Site $en;

    private Site $ru;

    protected function setUp(): void
    {
        parent::setUp();

        // The app lives on a single domain with locale prefixes (the user's setup).
        config(['app.url' => 'https://shop.example']);

        $this->default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'domain' => null]);
        $this->en = Site::factory()->create(['is_active' => true, 'prefix' => 'en', 'locale' => 'en', 'domain' => null]);
        $this->ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'domain' => null]);
    }

    private function rewriter(): LinkRewriter
    {
        return new LinkRewriter;
    }

    public function test_it_prefixes_a_root_relative_internal_link(): void
    {
        expect($this->rewriter()->rewrite('/contact', $this->default, $this->ru))->toBe('/ru/contact');
    }

    public function test_it_strips_our_host_from_an_absolute_internal_link(): void
    {
        expect($this->rewriter()->rewrite('https://shop.example/contact', $this->default, $this->ru))
            ->toBe('/ru/contact');
    }

    public function test_it_swaps_an_existing_locale_prefix(): void
    {
        // "en" is an active site prefix, so it is stripped before "ru" is applied.
        expect($this->rewriter()->rewrite('https://shop.example/en/contact', $this->default, $this->ru))
            ->toBe('/ru/contact');

        // And a prefixed source → the unprefixed default site.
        expect($this->rewriter()->rewrite('/ru/contact', $this->ru, $this->default))->toBe('/contact');
    }

    public function test_it_preserves_query_and_fragment(): void
    {
        expect($this->rewriter()->rewrite('/search?q=phone&sort=asc#results', $this->default, $this->ru))
            ->toBe('/ru/search?q=phone&sort=asc#results');
    }

    public function test_it_leaves_external_and_non_navigational_links_untouched(): void
    {
        $rewriter = $this->rewriter();

        expect($rewriter->rewrite('https://other.com/x', $this->default, $this->ru))->toBe('https://other.com/x')
            ->and($rewriter->rewrite('mailto:a@b.com', $this->default, $this->ru))->toBe('mailto:a@b.com')
            ->and($rewriter->rewrite('tel:+38612345678', $this->default, $this->ru))->toBe('tel:+38612345678')
            ->and($rewriter->rewrite('#section', $this->default, $this->ru))->toBe('#section')
            ->and($rewriter->rewrite('./relative', $this->default, $this->ru))->toBe('./relative')
            ->and($rewriter->rewrite('', $this->default, $this->ru))->toBe('');
    }

    public function test_it_emits_an_absolute_url_when_the_target_has_its_own_domain(): void
    {
        $de = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'de', 'domain' => 'de-shop.com']);

        expect($this->rewriter()->rewrite('/contact', $this->default, $de))->toBe('https://de-shop.com/contact');

        $dePrefixed = Site::factory()->create(['is_active' => true, 'prefix' => 'de', 'locale' => 'de', 'domain' => 'de2-shop.com']);

        expect($this->rewriter()->rewrite('/contact', $this->default, $dePrefixed))
            ->toBe('https://de2-shop.com/de/contact');
    }

    public function test_it_treats_a_site_own_domain_as_internal_when_rewriting(): void
    {
        $de = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'de', 'domain' => 'de-shop.com']);

        // A link to the de site's own domain, copied to ru, becomes a ru path.
        expect($this->rewriter()->rewrite('https://de-shop.com/contact', $de, $this->ru))->toBe('/ru/contact');
    }

    public function test_it_leaves_asset_storage_paths_untouched(): void
    {
        $rewriter = $this->rewriter();

        // Public storage dir is not a locale-scoped page route — must not be prefixed.
        expect($rewriter->rewrite('/storage/docs/brochure.pdf', $this->default, $this->ru))
            ->toBe('/storage/docs/brochure.pdf')
            ->and($rewriter->rewrite('https://shop.example/storage/banners/a.jpg', $this->default, $this->ru))
            ->toBe('https://shop.example/storage/banners/a.jpg');
    }

    public function test_it_normalizes_backslashes_to_prevent_protocol_relative_escape(): void
    {
        // WHATWG: browsers treat "\" as "/" for http(s); a same-host "/\evil.com/x"
        // must not survive as a protocol-relative off-site link.
        $result = $this->rewriter()->rewrite('https://shop.example/\\evil.com/phish', $this->default, $this->ru);

        expect($result)->toBe('/ru/evil.com/phish')
            ->and(str_contains($result, '\\'))->toBeFalse()
            ->and(str_starts_with($result, '/\\'))->toBeFalse();
    }

    public function test_it_strips_an_inactive_target_prefix_to_stay_idempotent(): void
    {
        // Target site prepared but not yet active: its prefix is NOT in the
        // active-only known set, yet a link already carrying it must not double up.
        $xxInactive = Site::factory()->create(['is_active' => false, 'prefix' => 'xx', 'locale' => 'xx', 'domain' => null]);

        expect($this->rewriter()->rewrite('/xx/promo', $this->default, $xxInactive))->toBe('/xx/promo');
    }

    public function test_it_strips_an_inactive_source_prefix(): void
    {
        $zzInactive = Site::factory()->create(['is_active' => false, 'prefix' => 'zz', 'locale' => 'zz', 'domain' => null]);

        expect($this->rewriter()->rewrite('/zz/contact', $zzInactive, $this->default))->toBe('/contact');
    }

    public function test_it_does_not_add_a_trailing_slash_to_root_links(): void
    {
        $rewriter = $this->rewriter();

        expect($rewriter->rewrite('/', $this->default, $this->ru))->toBe('/ru')
            ->and($rewriter->rewrite('https://shop.example', $this->default, $this->ru))->toBe('/ru')
            ->and($rewriter->rewrite('/', $this->default, $this->default))->toBe('/');
    }

    public function test_it_treats_a_different_port_on_our_host_as_external(): void
    {
        expect($this->rewriter()->rewrite('https://shop.example:8443/x', $this->default, $this->ru))
            ->toBe('https://shop.example:8443/x');
    }

    public function test_it_treats_www_of_our_host_as_internal(): void
    {
        expect($this->rewriter()->rewrite('https://www.shop.example/contact', $this->default, $this->ru))
            ->toBe('/ru/contact');
    }

    public function test_it_rewrites_protocol_relative_and_case_insensitive_internal_urls(): void
    {
        $rewriter = $this->rewriter();

        expect($rewriter->rewrite('//shop.example/contact', $this->default, $this->ru))->toBe('/ru/contact')
            ->and($rewriter->rewrite('https://SHOP.EXAMPLE/contact', $this->default, $this->ru))->toBe('/ru/contact');
    }
}
