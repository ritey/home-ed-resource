@props(['tier', 'resources'])
<section aria-labelledby="{{ $tier->value }}-heading" id="{{ $tier->value }}">
  <div @class(['section-head', 'section-head--paid' => $tier !== \App\Enums\Tier::Free])>
    <h2 id="{{ $tier->value }}-heading">{{ $tier->label() }}</h2>
    <span class="section-head__aside">- {{ $tier->aside() }}</span>
  </div>
  @if ($resources->isNotEmpty())
    <div class="entries">
      @foreach ($resources as $resource)
        <x-entry :resource="$resource" />
      @endforeach
    </div>
  @else
    <p class="section__empty">{{ $tier->emptyText() }}</p>
  @endif
</section>
