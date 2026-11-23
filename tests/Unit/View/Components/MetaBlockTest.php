<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\View\Components;

use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Blade;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Mockery;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentStaticPages\Ai\Agents\SeoAgent;
use Zoker\FilamentStaticPages\Tests\TestCase;
use Zoker\FilamentStaticPages\View\Components\MetaBlock;

class MetaBlockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        AlternateLinks::clear();
        config(['fsp.site_name' => 'Test Site', 'fsp.og_image_url' => null]);
        // Resolving the current site sets the app locale; do it before a test picks its own.
        SiteManager::getCurrentSite();
    }

    protected function tearDown(): void
    {
        AlternateLinks::clear();

        parent::tearDown();
    }

    /** @param array<string, mixed> $data */
    private function renderMeta(array $data): string
    {
        $block = new MetaBlock(array_merge([
            'title' => 'About us',
            'description' => null,
            'indexing' => 'index',
            'follow' => 'follow',
        ], $data));

        // The block only pushes to the "meta" stack, so yield it in the same render pass.
        return Blade::render("{!! \$block->render()->render() !!}@stack('meta')", ['block' => $block]);
    }

    public function test_renders_open_graph_tags_next_to_the_classic_meta(): void
    {
        app()->setLocale('ru');
        AlternateLinks::setCanonicalUrl('https://site.test/ru/about');

        $html = $this->renderMeta(['description' => 'Who we are', 'social_image' => 'https://cdn.test/about.jpg']);

        $this->assertStringContainsString('<title>About us</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Who we are">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="website">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="About us">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Who we are">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="https://site.test/ru/about">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="https://cdn.test/about.jpg">', $html);
        $this->assertStringContainsString('<meta property="og:site_name" content="Test Site">', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="ru_RU">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
    }

    public function test_stored_social_image_becomes_an_absolute_storage_url(): void
    {
        $html = $this->renderMeta(['social_image' => 'meta/about.jpg']);

        $this->assertMatchesRegularExpression('#<meta property="og:image" content="https?://[^"]+/meta/about\.jpg">#', $html);
    }

    public function test_falls_back_to_the_configured_default_image(): void
    {
        config(['fsp.og_image_url' => 'https://cdn.test/og-default.png']);

        $html = $this->renderMeta([]);

        $this->assertStringContainsString('<meta property="og:image" content="https://cdn.test/og-default.png">', $html);
    }

    public function test_omits_og_image_and_description_when_there_is_nothing_to_show(): void
    {
        $html = $this->renderMeta([]);

        $this->assertStringNotContainsString('og:image', $html);
        $this->assertStringNotContainsString('og:description', $html);
        $this->assertStringContainsString('<meta property="og:title" content="About us">', $html);
    }

    public function test_page_canonical_url_is_used_as_og_url(): void
    {
        $html = $this->renderMeta(['canonical_url' => 'https://site.test/canonical']);

        $this->assertStringContainsString('<meta property="og:url" content="https://site.test/canonical">', $html);
    }

    public function test_unknown_locale_is_emitted_as_is(): void
    {
        app()->setLocale('xx');

        $this->assertStringContainsString('<meta property="og:locale" content="xx">', $this->renderMeta([]));
    }

    public function test_schema_has_the_social_image_upload(): void
    {
        $names = array_map(fn ($component) => $component->getName(), array_filter(MetaBlock::getSchema(), fn ($component) => method_exists($component, 'getName')));

        $this->assertContains('social_image', $names);
    }

    public function test_generate_seo_tells_the_admin_when_the_ai_provider_has_no_credits(): void
    {
        config(['fsp.ai.enabled' => true]);
        SeoAgent::fake(fn () => throw InsufficientCreditsException::forProvider('openai', 429));

        $entry = collect(MetaBlock::getDescriptionField())->first(fn ($component) => $component->getName() === 'generateAI');
        $get = Mockery::mock(Get::class);
        $get->shouldReceive('__invoke')->andReturn(['title' => 'About us']);
        $set = Mockery::mock(Set::class);
        $set->shouldNotReceive('__invoke');

        $entry->getHintActions()[0]->call(['get' => $get, 'set' => $set]);

        Notification::assertNotified(__('fsp::lang.messages.ai_insufficient_credits'));
    }
}
