{{-- Plain text, not HTML: output is raw on purpose, so apostrophes are not entity-encoded. --}}
# {!! config('site.name') !!}

> {!! config('site.description') !!}

One UK home educating household keeps this list. Nobody pays to be on it,
there are no affiliate links, and every entry carries the date it was last
opened and checked. Entries that stop being useful are removed rather than
left up.

Region: United Kingdom
Contact: via {!! $base !!}#suggest
Search: {!! $base !!}resources?q={query} (also filters: category, age, cost, where)
Entries: {!! $total !!}
@if ($updated)
Last updated: {!! $updated->toDateString() !!}
@endif

@foreach ($sections as $section)
## {!! $section['heading'] !!}

@if ($section['blurb'])
{!! $section['blurb'] !!} All of them: {!! $section['url'] !!}

@endif
@foreach ($section['resources'] as $r)
- [{!! $r->title !!}]({!! $r->url !!}): {!! $r->description !!} ({!! collect([$r->tier->label(), $r->ageSpan(), $r->place() ?? 'Online or UK-wide', ...$r->tags])->filter()->join(' · ') !!}. Last checked {!! $r->last_checked->toDateString() !!}.)
@foreach ($r->more ?? [] as $paragraph)
  {!! $paragraph !!}
@endforeach
@endforeach

@endforeach
## Optional

- [Full list]({!! $base !!}resources): every entry, searchable, with its last-checked date.
