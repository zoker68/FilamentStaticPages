<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Zoker\FilamentStaticPages\Models\Menu as MenuModel;

class Menu extends Component
{
    public MenuModel $menu;

    public function __construct(private readonly string $code)
    {
        $this->menu = MenuModel::getMenu($this->code);
    }

    public function render(): View
    {
        return view('fsp::components.menu');
    }
}
