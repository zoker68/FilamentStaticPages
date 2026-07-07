<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Illuminate\View\View;
use Zoker\FilamentMultisite\Services\AlternateLinks;
use Zoker\FilamentStaticPages\Models\Page;

class PageController
{
    public function __invoke(?string $page = null): View
    {
        $pageModel = Page::url($page)->published()->firstOrFail();

        $this->setAlternateLinks($pageModel);

        return view('fsp::blocks', ['page' => $pageModel]); // @phpstan-ignore-line
    }

    protected function setAlternateLinks(Page $page): void
    {
        $links = [];

        // Build hreflang alternates from the explicit translation group (original +
        // its translations) rather than matching slugs — a translated page may have
        // a different slug. Each version carries its own site + localized url.
        foreach ($page->translationGroup() as $groupPage) {
            $site = $groupPage->site;

            if (! $groupPage->published || $site === null || ! $site->is_active) {
                continue;
            }

            $links[] = [
                'site' => $site,
                'url' => $groupPage->url
                    ? multisite_route('fsp.page', ['page' => $groupPage->url], site: $site)
                    : multisite_route('index', site: $site),
            ];
        }

        AlternateLinks::set($links);
    }
}
