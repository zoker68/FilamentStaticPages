<?php

namespace Zoker\FilamentStaticPages\Observers;

use Zoker\FilamentStaticPages\Models\Page;

class PageObserver
{
    public function saving(Page $page): void
    {
        cache()->forget(Page::CACHE_KEY_ROUTES);
    }

    public function deleted(Page $page): void
    {
        cache()->forget(Page::CACHE_KEY_ROUTES);
    }
}
