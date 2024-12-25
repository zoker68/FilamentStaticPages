@if ($image)
    <div {{ $attributes->class(['container pb-14']) }}>
        <div class="container">
            <a href="{{ $link }}" target="{{ $target }}">
                <picture>
                    <img class="w-full flex-shrink-0" src="{{ $storageUrl . $image }}" alt="{{ $alt }}">
                </picture>
            </a>
        </div>
    </div>
@endif
