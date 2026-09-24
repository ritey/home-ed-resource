{{-- Plain text, not HTML: output is raw on purpose, so apostrophes are not entity-encoded. --}}
# {!! config('site.name') !!}

> {!! config('site.description') !!}

One UK home educating household keeps this list. Nobody pays to be on it,
there are no affiliate links, and every entry carries the date it was last
opened and checked. Entries that stop being useful are removed rather than
left up.

Region: United Kingdom
Contact: via {!! $base !!}#suggest
Entries: {!! $total !!}
@if ($updated)
Last updated: {!! $updated->toDateString() !!}
@endif

@foreach ($tiers as $section)
## {!! $section['tier']->label() !!} — {!! $section['tier']->aside() !!}

@forelse ($section['resources'] as $r)
- [{!! $r->title !!}]({!! $r->url !!}): {!! $r->description !!} ({!! implode(' · ', $r->tags) !!}. Last checked {!! $r->last_checked->toDateString() !!}.)
@foreach ($r->more ?? [] as $paragraph)
  {!! $paragraph !!}
@endforeach
@empty
- {!! $section['tier']->emptyText() !!}
@endforelse

@endforeach
## Optional

- [Full list]({!! $base !!}): every entry with its last-checked date.
