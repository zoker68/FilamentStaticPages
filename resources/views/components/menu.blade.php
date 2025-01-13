@if(is_array($menu->items) && count($menu->items))
<ul>
    @foreach($menu->items as $item)
        <x-fsp::menu-item :item="$item" :menu="$menu"/>
    @endforeach
</ul>
@endif
