<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Zoker\FilamentStaticPages\Models\Page;
use Zoker\Shop\Http\Controllers\Controller;

class PageController extends Controller
{
    public function __invoke()
    {
        $pageUrl = ltrim(request()->route()->getName(), 'fsp.');

        $page = Page::whereUrl($pageUrl)->published()->firstOrFail();

        $page->load([
            'blocks' => fn ($q) => $q->orderBy('sort'),
        ]);

        return view('filament-static-pages::blocks', compact('page'));
    }
}
