@props([
    'title' => config('site.title'),
    'description' => config('site.meta_description'),
    'shareDescription' => config('site.share_description'),
    'canonical' => \App\Support\Directory::baseUrl(),
    'indexable' => true,
])
@php($base = \App\Support\Directory::baseUrl())
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="theme-color" content="#fdf3e4">
<meta name="color-scheme" content="light">
@if ($indexable)
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
@else
<meta name="robots" content="noindex, follow">
@endif

<!-- Open Graph -->
<meta property="og:site_name" content="{{ config('site.name') }}">
<meta property="og:locale" content="en_GB">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $shareDescription }}">
<meta property="og:image" content="{{ $base }}og-image.png">
<meta property="og:image:secure_url" content="{{ $base }}og-image.png">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ config('site.image_alt') }}">

<!-- Twitter / X -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $shareDescription }}">
<meta name="twitter:image" content="{{ $base }}og-image.png">
<meta name="twitter:image:alt" content="{{ config('site.image_alt') }}">

<!-- Icons -->
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

<link rel="preload" href="{{ Vite::asset('resources/fonts/bricolage-grotesque-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ Vite::asset('resources/fonts/karla-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{ $head ?? '' }}
</head>

<body>
<a class="skip-link" href="#main">Skip to the content</a>

<div class="page">

  <header class="masthead">
    <a class="masthead__brand" href="/">
      <span class="brand-dot" aria-hidden="true"></span>
      <span class="wordmark">{{ config('site.name') }}</span>
    </a>
    <nav aria-label="Primary">
      <a href="{{ route('browse') }}">The list</a>
      <a href="/#who">Who we are</a>
      <a href="/#suggest">Send us one</a>
    </nav>
  </header>

  {{ $slot }}

</div>

</body>
</html>
