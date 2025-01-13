<!-- breadcrumbs -->
<div class="container py-5 flex items-center">
    @foreach($breadcrumbs as $breadcrumb)
        @if(! $loop->last)
            <a href="{{ $menu->getUrl($breadcrumb) }}" class="flex items-center hover:underline hover:text-secondary">
        @endif

        @if($loop->first)
            <span class="text-primary hover:text-secondary duration-300">
                <svg width="17" height="17" viewBox="0 0 32 32">
                    <path fill="currentColor"
                          d="m16 2.594l-.719.687l-13 13L3.72 17.72L5 16.437V28h9V18h4v10h9V16.437l1.281 1.282l1.438-1.438l-13-13zm0 2.844l9 9V26h-5V16h-8v10H7V14.437z"/>
                </svg>
            </span>
        @else
            {{ $breadcrumb['title'] }}
        @endif

        @if(! $loop->last)
            <span>
                <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M10 6L8.59 7.41L13.17 12l-4.58 4.59L10 18l6-6l-6-6z"/></svg>
            </span>
        @endif
        @if(! $loop->last)
            </a>
        @endif
    @endforeach
</div>
<!-- breadcrumbs end-->
