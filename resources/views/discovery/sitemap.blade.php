{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($urls as $url)
  <url>
    <loc>{{ $url['loc'] }}</loc>
@if ($url['lastmod'])
    <lastmod>{{ $url['lastmod'] }}</lastmod>
@endif
    <changefreq>monthly</changefreq>
    <priority>{{ $url['priority'] }}</priority>
@if ($url['image'] ?? false)
    <image:image>
      <image:loc>{{ $base }}og-image.png</image:loc>
      <image:title>{{ config('site.name') }}</image:title>
    </image:image>
@endif
  </url>
@endforeach
</urlset>
