<x-dynamic-component :component="$page->getLayoutComponent()">
    @foreach($page->blocks as $block)
        <x-dynamic-component component="{{ $block->getViewComponent() }}" :data="$block->data" />
    @endforeach
</x-dynamic-component>
