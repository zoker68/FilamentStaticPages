<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Tests\Unit\Services;

use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlockLinkRewriter;
use Zoker\FilamentStaticPages\Services\LinkRewriter;
use Zoker\FilamentStaticPages\Tests\TestCase;

class BlockLinkRewriterTest extends TestCase
{
    private Site $default;

    private Site $ru;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://bsg-europe.com']);

        $this->default = Site::factory()->create(['is_active' => true, 'prefix' => null, 'locale' => 'en', 'domain' => null]);
        $this->ru = Site::factory()->create(['is_active' => true, 'prefix' => 'ru', 'locale' => 'ru', 'domain' => null]);
    }

    private function rewriter(): BlockLinkRewriter
    {
        return new BlockLinkRewriter(new LinkRewriter);
    }

    public function test_it_rewrites_only_declared_link_fields_across_block_types(): void
    {
        $blocks = [
            ['type' => 'Banner', 'data' => ['link' => '/contact', 'alt' => 'Hello', 'target' => '_blank', 'image' => 'banners/a.jpg']],
            ['type' => 'Slider', 'data' => ['slides' => [
                ['heading' => 'H1', 'link' => '/delivery', 'target' => '_self'],
                ['heading' => 'H2', 'link' => 'https://other.com/x'],
            ]]],
            ['type' => 'ImageWithText', 'data' => ['template' => 'small-icon', 'blocks' => [
                ['heading' => 'Feat', 'link' => ['text' => 'More', 'url' => '/faq', 'target' => '_self']],
            ]]],
        ];

        $result = $this->rewriter()->rewrite($blocks, $this->default, $this->ru);

        // Whole-structure assertion: only the three declared link values change,
        // every other key (target, heading, template, external link) stays intact.
        $expected = [
            ['type' => 'Banner', 'data' => ['link' => '/ru/contact', 'alt' => 'Hello', 'target' => '_blank', 'image' => 'banners/a.jpg']],
            ['type' => 'Slider', 'data' => ['slides' => [
                ['heading' => 'H1', 'link' => '/ru/delivery', 'target' => '_self'],
                ['heading' => 'H2', 'link' => 'https://other.com/x'],
            ]]],
            ['type' => 'ImageWithText', 'data' => ['template' => 'small-icon', 'blocks' => [
                ['heading' => 'Feat', 'link' => ['text' => 'More', 'url' => '/ru/faq', 'target' => '_self']],
            ]]],
        ];

        expect($result)->toBe($expected);
    }

    public function test_it_rewrites_href_links_inside_rich_text_html(): void
    {
        $blocks = [
            ['type' => 'Content', 'data' => [
                'content' => '<p><a href="/contact">Contact</a> <a href="https://other.com/x">ext</a> <a href="mailto:a@b.com">mail</a></p>',
                'css_class' => 'lead',
            ]],
        ];

        $result = $this->rewriter()->rewrite($blocks, $this->default, $this->ru);

        expect($result[0]['data']['content'])
            ->toContain('href="/ru/contact"')      // internal → localised
            ->toContain('href="https://other.com/x"') // external → untouched
            ->toContain('href="mailto:a@b.com"')   // mailto → untouched
            ->and($result[0]['data']['css_class'])->toBe('lead');
    }

    public function test_it_leaves_breadcrumbs_builder_urls_untouched(): void
    {
        $blocks = [
            ['type' => 'Breadcrumbs', 'data' => ['breadcrumbs' => [
                ['title' => 'Home', 'url' => [['type' => 'fsp', 'data' => ['page' => 'home']]]],
            ]]],
        ];

        $result = $this->rewriter()->rewrite($blocks, $this->default, $this->ru);

        expect($result)->toBe($blocks);
    }

    public function test_rewrite_page_localises_links_and_persists(): void
    {
        $page = $this->makePage($this->ru, 'contact', [
            ['type' => 'Banner', 'data' => ['link' => '/delivery', 'alt' => 'x']],
        ]);

        $this->rewriter()->rewritePage($page, $this->default, 0);

        $reloaded = Page::withoutGlobalScope('multisite')->find($page->id);

        expect($reloaded->content[0]['data']['link'])->toBe('/ru/delivery');
    }

    public function test_rewrite_page_only_touches_the_appended_tail(): void
    {
        $page = $this->makePage($this->ru, 'contact', [
            ['type' => 'Banner', 'data' => ['link' => '/keep', 'alt' => 'existing']],
            ['type' => 'Banner', 'data' => ['link' => '/new', 'alt' => 'appended']],
        ]);

        // fromIndex = 1 → only the second (appended) block is rewritten.
        $this->rewriter()->rewritePage($page, $this->default, 1);

        $reloaded = Page::withoutGlobalScope('multisite')->find($page->id);

        expect($reloaded->content[0]['data']['link'])->toBe('/keep')
            ->and($reloaded->content[1]['data']['link'])->toBe('/ru/new');
    }

    /**
     * @param  array<int, array<string, mixed>>  $content
     */
    private function makePage(Site $site, string $url, array $content): Page
    {
        $page = new Page(['name' => 'P', 'url' => $url, 'layout' => 'app', 'published' => true]);
        $page->setSite($site);
        $page->content = $content;
        $page->save();

        return $page;
    }
}
