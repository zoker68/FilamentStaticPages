@props([
    'menu',
    'item' => []
])
<li @if($item['css'])class="{{ $item['css'] }}"@endif>
    <a href="{{ $menu->getUrl($item) }}">
        {{ $item['label'] }}
        @if($item['hasSubmenu'] && count($item['submenu']))
            <svg width="14" height="14" viewBox="0 0 32 32">
                <path fill="currentColor"
                      d="M4.219 10.781L2.78 12.22l12.5 12.5l.719.687l.719-.687l12.5-12.5l-1.438-1.438L16 22.562z"/>
            </svg>
        @endif
    </a>

    @if($item['hasSubmenu'] && count($item['submenu']))
    <ul class="submenu">
        @foreach($item['submenu'] as $subitem)
        <x-fsp::menu-item :item="$subitem" :menu="$menu" />
        @endforeach
    </ul>
    @endif
</li>
