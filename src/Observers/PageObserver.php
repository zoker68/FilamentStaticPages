<?php

namespace Zoker\FilamentStaticPages\Observers;

use Zoker\FilamentStaticPages\Models\Page;

class PageObserver
{
    public function saving(Page $page): void
    {
        $this->clearCache();
    }

    public function deleted(Page $page): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        cache()->forget(Page::CACHE_KEY_ROUTES);
        cache()->forget(Page::CACHE_KEY_ALLOWED_URLS);
    }
}
