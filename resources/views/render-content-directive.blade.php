@foreach($blocks as $block)
    <x-dynamic-component component="{{ $page->getBlockViewComponent($block['type']) }}" :data="$block['data']" :page="$page" />
@endforeach
