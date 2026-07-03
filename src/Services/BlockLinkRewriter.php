<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Services;

use Illuminate\Support\Arr;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Classes\BlocksComponentRegistry;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Support\BlockDataPaths;

/**
 * Rewrites the links stored inside a blocks array when a page is copied to
 * another site. Which fields hold links is declared per block via the static
 * $links / $htmlLinks properties on each block component, so the structure and
 * all non-link fields stay intact (mirrors BlockContentTranslator).
 *
 *  - $links fields go through LinkRewriter (host + locale prefix);
 *  - $htmlLinks fields are rich-text HTML — their href="..." attributes are rewritten.
 *
 * Canonical URLs are intentionally NOT handled here: they are an SEO/render-time
 * concern resolved via the multisite AlternateLinks service, not stored per copy.
 */
class BlockLinkRewriter
{
    public function __construct(private readonly LinkRewriter $linkRewriter) {}

    /**
     * Rewrite the links of a freshly copied page in place and persist it.
     * Only blocks from $fromIndex onward are touched, so appended blocks are
     * localised without rewriting the target's existing ones (matches the
     * translation job's append-safe behaviour). Saved quietly: the copy already
     * fired the page observer, and a content-only change needs no route cache bust.
     */
    public function rewritePage(Page $page, Site $source, int $fromIndex = 0): void
    {
        $target = $page->site;

        if ($target === null) {
            return;
        }

        $content = $page->content ?? [];
        $head = array_slice($content, 0, $fromIndex);
        $tail = array_slice($content, $fromIndex);

        $page->content = array_merge($head, $this->rewrite($tail, $source, $target));
        $page->saveQuietly();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function rewrite(array $blocks, Site $source, Site $target): array
    {
        if ($blocks === []) {
            return $blocks;
        }

        foreach ($blocks as $blockIndex => $block) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? null;

            if (! is_string($type) || ! is_array($data)) {
                continue;
            }

            foreach ($this->keysFor($type, 'links') as $keyPath) {
                foreach (BlockDataPaths::expand($data, $keyPath) as $concretePath) {
                    $value = Arr::get($blocks[$blockIndex]['data'], $concretePath);

                    if (is_string($value) && trim($value) !== '') {
                        Arr::set(
                            $blocks[$blockIndex]['data'],
                            $concretePath,
                            $this->linkRewriter->rewrite($value, $source, $target)
                        );
                    }
                }
            }

            foreach ($this->keysFor($type, 'htmlLinks') as $keyPath) {
                foreach (BlockDataPaths::expand($data, $keyPath) as $concretePath) {
                    $value = Arr::get($blocks[$blockIndex]['data'], $concretePath);

                    if (is_string($value) && $value !== '') {
                        Arr::set(
                            $blocks[$blockIndex]['data'],
                            $concretePath,
                            $this->rewriteHtmlLinks($value, $source, $target)
                        );
                    }
                }
            }
        }

        return $blocks;
    }

    /**
     * Rewrite href="..." / href='...' attributes inside a rich-text HTML string,
     * running each URL through LinkRewriter. Non-link hrefs (mailto:, #anchor,
     * external) are returned untouched by the rewriter, and all other markup is
     * left intact.
     */
    private function rewriteHtmlLinks(string $html, Site $source, Site $target): string
    {
        return (string) preg_replace_callback(
            '/(href\s*=\s*)(["\'])(.*?)\2/i',
            fn (array $m): string => $m[1] . $m[2] . $this->linkRewriter->rewrite($m[3], $source, $target) . $m[2],
            $html
        );
    }

    /**
     * @return array<int, string>
     */
    private function keysFor(string $type, string $property): array
    {
        $class = BlocksComponentRegistry::get($type);

        if ($class === null) {
            return [];
        }

        if ($property === 'links' && property_exists($class, 'links')) {
            /** @var array<int, string> */
            return $class::$links;
        }

        if ($property === 'htmlLinks' && property_exists($class, 'htmlLinks')) {
            /** @var array<int, string> */
            return $class::$htmlLinks;
        }

        return [];
    }
}
