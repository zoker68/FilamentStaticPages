<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Models\Page;

class RenderContentDirective extends Component
{
    /** @var array<array-key, array<string, mixed>> */
    public array $blocks = [];

    public function __construct(string $code, public Page $page)
    {
        $this->blocks = Content::where('code', $code)->value('content') ?? [];
    }

    public function render(): View
    {
        /** @phpstan-ignore-next-line */
        return view('fsp::render-content-directive');
    }
}
