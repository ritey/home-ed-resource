@props(['resource'])
<article class="entry">
  <div class="entry__main">
    <div class="entry__head">
      <h3 class="entry__title">
        <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $resource->title }}</a>
      </h3>
      <span class="entry__checked">we last looked <time datetime="{{ $resource->last_checked->toDateString() }}">{{ $resource->last_checked->format('j M') }}</time></span>
    </div>
    <p class="entry__description">{{ $resource->description }}</p>
    @foreach ($resource->more ?? [] as $paragraph)
      <p class="entry__more">{{ $paragraph }}</p>
    @endforeach
    <ul class="entry__tags">
      @foreach ($resource->tags as $tag)
        <li class="tag">{{ $tag }}</li>
      @endforeach
      <li class="entry__domain">{{ $resource->domain }}</li>
    </ul>
  </div>
  @if ($resource->image)
    {{-- Decorative unless it has alt text: an aria-hidden wrapper would stop
         a screen reader announcing the alt, so it is only hidden when empty. --}}
    <a class="entry__thumb" href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer nofollow"
       @unless ($resource->hasMeaningfulImage()) tabindex="-1" aria-hidden="true" @endunless>
      <img src="{{ asset($resource->image) }}" alt="{{ $resource->image_alt ?? '' }}" width="266" height="140"
           loading="lazy" decoding="async">
    </a>
  @else
    <span class="entry__thumb entry__thumb--empty" aria-hidden="true">{{ $resource->domain }}</span>
  @endif
</article>
