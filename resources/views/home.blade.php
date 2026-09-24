<x-layouts.site>
  <x-slot:head>
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
  </x-slot:head>

  <main>

    <div class="intro">
      <h1>Home Education Resources UK – Free &amp; Paid Resources</h1>
      <p class="intro__lede">We're a home ed family in the UK, and we kept forgetting which websites were any good. So we started writing them down. Here's the list - free things first, paid things underneath, with a note on who each one suited us for.</p>
      <ul class="pills">
        {{-- The first two are derived from the data, per the handoff. --}}
        <li class="pill">{{ $directory->resources->count() }} {{ Str::plural('thing', $directory->resources->count()) }} so far</li>
        @if ($updated)
          <li class="pill">Updated {{ $updated->format('j F') }}</li>
        @endif
        <li class="pill">{{ config('site.standing_pill') }}</li>
      </ul>
    </div>

    @foreach ($tiers as $section)
      <x-tier-section :tier="$section['tier']" :resources="$section['resources']" />
    @endforeach

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

  <footer class="footer">
    <p class="footer__disclaimer">These are other people's websites, so do have a look yourself before you rely on one - us listing it isn't a promise or guarantee it's great for everyone.</p>
    <div class="footer__meta">
      <span>Want an email when we add something? <a href="#suggest">Yes please</a></span>
      <span>&copy; {{ ($updated ?? now())->year }} {{ config('site.name') }}</span>
    </div>
  </footer>
</x-layouts.site>
