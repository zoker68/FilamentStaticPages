<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Zoker\FilamentStaticPages\Models\Page;
use Zoker\Shop\Http\Controllers\Controller;

class PageController extends Controller
{
    public function __invoke()
    {
        $routeName = request()->route()->getName();

        $pageUrl = substr($routeName, 4);

        $page = Page::whereUrl($pageUrl)->published()->firstOrFail();

        return view('fsp::blocks', compact('page'));
    }
}
