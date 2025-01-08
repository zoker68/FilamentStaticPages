<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Zoker\FilamentStaticPages\Models\Page;
use Zoker\Shop\Http\Controllers\Controller;

class PageController extends Controller
{
    public function __invoke()
    {
        $routeName = request()->route()->getName();
        $page = null;

        if (str_starts_with($routeName, 'fsp.')) {
            $pageUrl = substr($routeName, 4);
            $page = Page::url($pageUrl)->published()->firstOrFail();
        } elseif ($routeName === 'index') {
            $page = Page::whereNull('url')->firstOrFail();
        }

        if (! $page) {
            abort(404);
        }

        return view('fsp::blocks', compact('page'));
    }
}
