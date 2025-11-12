<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Illuminate\View\View;
use Zoker\FilamentStaticPages\Models\Page;

class PageController
{
    public function __invoke(): View
    {
        $routeName = request()->route()->getName();
        $page = null;

        if (str_starts_with($routeName, 'fsp.')) {
            $pageUrl = substr($routeName, 4);
            $page = Page::url($pageUrl)->published()->firstOrFail();
        } elseif ($routeName === 'index' || $routeName === 'multisite.index') {
            $page = Page::whereNull('url')->firstOrFail();
        }

        if (! $page) {
            abort(404);
        }

        /** @phpstan-ignore-next-line */
        return view('fsp::blocks', compact('page'));
    }
}
