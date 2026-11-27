<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\View\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Tests\TestCase;
use Zoker\FilamentStaticPages\View\Components\MetaBlock;

class MetaBlockStructuredDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AlternateLinks::clear();

        // The routes Route::multisite() registers for the home page: plain and with the site prefix.
        Route::get('/', fn () => '')->name('index');
        Route::get('{multisite_prefix}', fn () => '')->name('multisite.index');

        config([
            'fsp.site_name' => 'Test Site',
            'fsp.og_image_url' => null,
            'fsp.structured_data.enabled' => true,
            'fsp.structured_data.organization' => [
                'type' => 'Organization',
                'legal_name' => null,
                'alternate_name' => null,
                'logo_url' => null,
                'same_as' => [],
                'email' => null,
                'telephone' => null,
                'vat_id' => null,
                'address' => ['street' => null, 'locality' => null, 'postal_code' => null, 'country' => null],
            ],
            'fsp.structured_data.return_policy' => null,
        ]);

        SiteManager::getCurrentSite();
    }

    protected function tearDown(): void
    {
        AlternateLinks::clear();

        parent::tearDown();
    }

    public function test_home_page_carries_organization_and_website_from_the_config(): void
    {
        config([
            'fsp.structured_data.organization' => [
                'type' => 'OnlineStore',
                'legal_name' => 'Test Site d.o.o.',
                'alternate_name' => 'TS',
                'logo_url' => 'https://cdn.test/logo.png',
                'same_as' => ['https://www.facebook.com/testsite', '', 'https://www.instagram.com/testsite'],
                'email' => 'info@site.test',
                'telephone' => '+386 1 234 56 78',
                'vat_id' => 'SI12345678',
                'address' => ['street' => 'Main street 1', 'locality' => 'Ljubljana', 'postal_code' => '1000', 'country' => 'SI'],
            ],
        ]);

        [$organization, $website] = $this->graph($this->renderMeta($this->homePage()));

        $this->assertSame([
            '@type' => 'OnlineStore',
            '@id' => 'http://localhost#organization',
            'name' => 'Test Site',
            'legalName' => 'Test Site d.o.o.',
            'alternateName' => 'TS',
            'url' => 'http://localhost',
            'logo' => 'https://cdn.test/logo.png',
            'sameAs' => ['https://www.facebook.com/testsite', 'https://www.instagram.com/testsite'],
            'email' => 'info@site.test',
            'telephone' => '+386 1 234 56 78',
            'vatID' => 'SI12345678',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Main street 1',
                'addressLocality' => 'Ljubljana',
                'postalCode' => '1000',
                'addressCountry' => 'SI',
            ],
        ], $organization);

        $this->assertSame([
            '@type' => 'WebSite',
            '@id' => 'http://localhost#website',
            'name' => 'Test Site',
            'alternateName' => 'TS',
            'url' => 'http://localhost',
            'publisher' => ['@id' => 'http://localhost#organization'],
        ], $website);
    }

    public function test_empty_fields_are_left_out(): void
    {
        [$organization, $website] = $this->graph($this->renderMeta($this->homePage()));

        $this->assertSame(['@type', '@id', 'name', 'url'], array_keys($organization));
        $this->assertSame('Organization', $organization['@type']);
        $this->assertSame(['@type', '@id', 'name', 'url', 'publisher'], array_keys($website));
    }

    public function test_a_relative_logo_becomes_an_absolute_url(): void
    {
        config(['fsp.structured_data.organization.logo_url' => '/images/logo.png']);

        [$organization] = $this->graph($this->renderMeta($this->homePage()));

        $this->assertSame('http://localhost/images/logo.png', $organization['logo']);
    }

    public function test_there_is_no_search_action(): void
    {
        $html = $this->renderMeta($this->homePage());

        [, $website] = $this->graph($html);

        $this->assertArrayNotHasKey('potentialAction', $website);
        $this->assertStringNotContainsString('SearchAction', $html);
    }

    public function test_return_policy_appears_only_when_it_is_configured(): void
    {
        [$organization] = $this->graph($this->renderMeta($this->homePage()));
        $this->assertArrayNotHasKey('hasMerchantReturnPolicy', $organization);

        config(['fsp.structured_data.return_policy' => ['days' => 14, 'countries' => ['SI', 'DE'], 'fees' => 'FreeReturn', 'method' => 'ReturnByMail']]);

        [$organization] = $this->graph($this->renderMeta($this->homePage()));

        $this->assertSame([
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => ['SI', 'DE'],
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => 14,
            'returnFees' => 'https://schema.org/FreeReturn',
            'returnMethod' => 'https://schema.org/ReturnByMail',
        ], $organization['hasMerchantReturnPolicy']);
    }

    public function test_return_policy_without_countries_is_left_out(): void
    {
        config(['fsp.structured_data.return_policy' => ['days' => 14, 'countries' => [], 'fees' => 'FreeReturn']]);

        [$organization] = $this->graph($this->renderMeta($this->homePage()));

        $this->assertArrayNotHasKey('hasMerchantReturnPolicy', $organization);
    }

    public function test_other_pages_have_no_tag(): void
    {
        $this->assertStringNotContainsString('application/ld+json', $this->renderMeta(new Page(['name' => 'About', 'url' => 'about'])));
        $this->assertStringNotContainsString('application/ld+json', $this->renderMeta(null));
    }

    public function test_no_tag_when_disabled(): void
    {
        config(['fsp.structured_data.enabled' => false]);

        $html = $this->renderMeta($this->homePage());

        $this->assertStringContainsString('<title>Home</title>', $html);
        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function test_no_tag_without_a_site_name(): void
    {
        config(['fsp.site_name' => '']);

        $this->assertStringNotContainsString('application/ld+json', $this->renderMeta($this->homePage()));
    }

    public function test_script_tag_in_the_site_name_does_not_break_out_of_the_tag(): void
    {
        config(['fsp.site_name' => 'Foo </script><script>alert(1)</script>']);

        $html = $this->renderMeta($this->homePage());

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        [$organization] = $this->graph($html);
        $this->assertSame('Foo </script><script>alert(1)</script>', $organization['name']);
    }

    public function test_prefixed_site_points_to_its_own_home_page(): void
    {
        $site = Site::factory()->create(['prefix' => 'fr', 'locale' => 'fr', 'is_active' => true]);
        SiteManager::setCurrentSite($site);

        [$organization, $website] = $this->graph($this->renderMeta($this->homePage()));

        $this->assertSame('http://localhost/fr', $organization['url']);
        $this->assertSame('http://localhost/fr#organization', $organization['@id']);
        $this->assertSame('http://localhost/fr', $website['url']);
        $this->assertSame(['@id' => 'http://localhost/fr#organization'], $website['publisher']);
    }

    private function homePage(): Page
    {
        return new Page(['name' => 'Home', 'url' => null]);
    }

    private function renderMeta(?Page $page): string
    {
        $block = new MetaBlock(['title' => 'Home', 'description' => null, 'indexing' => 'index', 'follow' => 'follow'], $page);

        // The block only pushes to the "meta" stack, so yield it in the same render pass.
        return Blade::render("{!! \$block->render()->render() !!}@stack('meta')", ['block' => $block]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function graph(string $html): array
    {
        $this->assertSame(1, preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches));

        $data = json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $data['@context']);

        return $data['@graph'];
    }
}
