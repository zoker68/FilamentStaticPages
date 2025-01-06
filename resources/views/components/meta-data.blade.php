@push('meta')
    <title>{{ $title }}</title>

    @if($description)<meta name="description" content="{{ $description }}">@endif

    <meta name="robots" content="{{ $indexing }}, {{ $follow }}">

@endpush
