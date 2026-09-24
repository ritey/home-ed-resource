<x-layouts.site :title="$title" :description="$description" :share-description="$description"
                :canonical="$canonical" :indexable="$indexable">
  <x-slot:head>
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
  </x-slot:head>

  <main id="main">

    <div class="intro intro--compact">
      @if ($crumbs)
        <nav class="crumbs" aria-label="Breadcrumb">
          <a href="{{ route('browse') }}">The full list</a>
          <span aria-hidden="true">/</span>
          <span aria-current="page">{{ $heading }}</span>
        </nav>
      @endif
      <h1>{{ $heading }}</h1>
      <p class="intro__lede">{{ $lede }}</p>
    </div>

    <x-filters :filters="$filters" :categories="$categories" :nations="$nations" :ages="$ages" :tiers="$tiers" />

    <section aria-labelledby="results-heading" id="results">
      <div class="results-bar">
        <h2 id="results-heading" class="results-bar__count">
          {{ $results->total() }} {{ Str::plural('thing', $results->total()) }}
        </h2>
        @if ($filters->summary())
          <p class="results-bar__summary">{{ implode(' · ', $filters->summary()) }}</p>
        @endif
        @if ($filters->isFiltered())
          <a class="results-bar__clear" href="{{ route('browse') }}">Clear all</a>
        @endif
      </div>

      @if ($results->isNotEmpty())
        <div class="entries">
          @foreach ($results as $resource)
            <x-entry :resource="$resource" />
          @endforeach
        </div>
        {{ $results->links('pagination') }}
      @else
        <div class="section__empty">
          @if ($filters->cost === \App\Enums\Tier::Paid)
            <p>{{ $filters->cost->emptyText() }}</p>
          @else
            <p>Nothing matches that yet. Try fewer filters or a shorter search.</p>
          @endif
          <p>If you know something that belongs here, <a href="/#suggest">tell us about it</a>.</p>
        </div>
      @endif
    </section>

  </main>

  <x-footer :year="now()->year" />
</x-layouts.site>
