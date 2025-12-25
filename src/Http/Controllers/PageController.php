<?php

namespace Zoker\FilamentStaticPages\Http\Controllers;

use Illuminate\View\View;
use Zoker\FilamentStaticPages\Models\Page;

class PageController
{
    public function __invoke(?string $page = null): View
    {
        $pageModel = Page::url($page)->published()->firstOrFail();

        return view('fsp::blocks', ['page' => $pageModel]); // @phpstan-ignore-line
    }
}
