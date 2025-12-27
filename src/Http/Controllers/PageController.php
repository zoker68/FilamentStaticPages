<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Illuminate\View\View;
use Zoker\FilamentMultisite\Facades\SiteManager;
use Zoker\FilamentMultisite\Models\Site;
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
        $sites = Site::getForDomain(SiteManager::getCurrentSite()->domain);
        $pages = Page::forSites($sites->pluck('id')->toArray())->url($page->url)->published()->get();

        foreach ($sites as $site) {
            $page = $pages->firstWhere('site_id', $site->id);
            if (! $page) {
                continue;
            }

            $links[] = [
                'site' => $site,
                'url' => $page->url
                    ? multisite_route('fsp.page', ['page' => $page->url], site: $site)
                    : multisite_route('index', site: $site),
            ];
        }

        AlternateLinks::set($links);
    }
}
