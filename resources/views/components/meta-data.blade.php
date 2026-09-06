@push('meta')
    <title>{{ $title }}</title>

    @if($description)<meta name="description" content="{{ $description }}">@endif

    <meta name="robots" content="{{ $indexing }}, {{ $follow }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    @if($description)<meta property="og:description" content="{{ $description }}">@endif
    @if($ogUrl)<meta property="og:url" content="{{ $ogUrl }}">@endif
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta name="twitter:card" content="summary_large_image">
@endpush
