<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Illuminate\View\View;
use Zoker\FilamentStaticPages\Models\Page;

class PageController
{
    public function __invoke(): View
    {
        $page = $this->resolvePage();

        /** @phpstan-ignore-next-line */
        return view('fsp::blocks', compact('page'));
    }

    private function resolvePage(): Page
    {
        $routeName = request()->route()->getName();
        $pageUrl = $this->extractPageUrl($routeName);

        return $pageUrl === null
            ? Page::whereNull('url')->published()->firstOrFail()
            : Page::url($pageUrl)->published()->firstOrFail();
    }

    private function extractPageUrl(string $routeName): ?string
    {
        return match (true) {
            str_starts_with($routeName, 'multisite.fsp.') => substr($routeName, 14),
            str_starts_with($routeName, 'fsp.') => substr($routeName, 4),
            in_array($routeName, ['index', 'multisite.index']) => null,
            default => abort(404),
        };
    }
}
