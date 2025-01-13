<ul>
    @foreach($menu->items as $item)
        <x-fsp::menu-item :item="$item" :menu="$menu"/>
    @endforeach
</ul>

