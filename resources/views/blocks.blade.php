<x-dynamic-component :component="$page->getLayoutComponent()">

    @foreach($page->content as $block)
        <x-dynamic-component component="{{ $page->getBlockViewComponent($block['type']) }}" :data="$block['data']" :page="$page" />
    @endforeach
</x-dynamic-component>
