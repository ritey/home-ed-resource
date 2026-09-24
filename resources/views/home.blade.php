<x-layouts.site>
  <x-slot:head>
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
  </x-slot:head>

  <main id="main">

    <div class="intro">
      <h1>Home Education Resources UK – Free &amp; Paid Resources</h1>
      <p class="intro__lede">We're a home ed family in the UK, and we kept forgetting which websites were any good. So we started writing them down. Here's the list - sorted by subject, with a note on who each one suited us for.</p>
      <ul class="pills">
        {{-- The first two are derived from the data, per the handoff. --}}
        <li class="pill">{{ $total }} {{ Str::plural('thing', $total) }} so far</li>
        @if ($updated)
          <li class="pill">Updated {{ $updated->format('j F') }}</li>
        @endif
        <li class="pill">{{ config('site.standing_pill') }}</li>
      </ul>
      <x-search />
    </div>

    <section aria-labelledby="spotlight-heading" id="spotlight">
      <div class="section-head">
        <h2 id="spotlight-heading">Worth a look</h2>
        <span class="section-head__aside">- a few we keep coming back to</span>
      </div>
      <div class="entries">
        @foreach ($spotlight as $resource)
          <x-entry :resource="$resource" />
        @endforeach
      </div>
      <p class="section__more"><a href="{{ route('browse') }}">See all {{ $total }} on the full list</a></p>
    </section>

    <section aria-labelledby="browse-heading" id="browse">
      <div class="section-head">
        <h2 id="browse-heading">Find something</h2>
        <span class="section-head__aside">- by subject, or by what you're trying to sort out</span>
      </div>
      @foreach ($groups as $group)
        <x-category-tiles :heading="$group['group']->heading()" :categories="$group['categories']" />
      @endforeach
      @if ($emptyCategories->isNotEmpty())
        <p class="section__empty">Nothing under {{ $emptyCategories->pluck('name')->join(', ', ' or ') }} yet. They'll fill in as we find things worth listing.</p>
      @endif
    </section>

    <section aria-labelledby="near-heading" id="near">
      <div class="section-head">
        <h2 id="near-heading">Near you</h2>
        <span class="section-head__aside">- groups, places and exam centres by region</span>
      </div>
      @if ($regions->isNotEmpty())
        <ul class="pills pills--links">
          @foreach ($regions as $region)
            <li><a class="pill" href="{{ route('browse.region', $region) }}">{{ $region->name }} <span class="pill__count">{{ $region->resources_count }}</span></a></li>
          @endforeach
        </ul>
      @else
        <p class="section__empty">Everything on the list so far works from anywhere. If there's a group, a place or an exam centre near you that's worth knowing about, <a href="#suggest">tell us</a>.</p>
      @endif
    </section>

  </main>

  <div class="two-up">
    <div class="two-up__col" id="who">
      <h2>Who's behind this</h2>
      <p>Just a family from the UK. If something stops being useful we take it off rather than leave it sitting on the site. Each resource says when we last checked it.</p>
    </div>
    <div class="two-up__col" id="suggest">
      <h2>Got one for us?</h2>
      <p>If something's worked for you, we'd love to hear about it. The link and a sentence about who it suited is plenty.</p>
      {{-- Assembled into a mailto by resources/js/app.js; the address is never whole in the markup. --}}
      <a class="button" id="contact-email" href="#suggest" data-u="{{ $emailUser }}" data-d="{{ $emailDomain }}"><span class="email-reverse">{{ $emailReversed }}</span></a>
    </div>
  </div>

  <x-footer :year="($updated ?? now())->year" />
</x-layouts.site>
