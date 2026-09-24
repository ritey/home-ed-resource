@props(['filters', 'categories', 'nations', 'ages', 'tiers'])
{{-- One GET form, no JS: pick filters, press Search. Every result is a URL. --}}
<form class="filters" action="{{ route('browse') }}" method="get" role="search" aria-label="Search and filter the list">
  <div class="search">
    <label class="visually-hidden" for="filter-q">Search the list</label>
    <input class="field field--search" type="search" id="filter-q" name="q" value="{{ $filters->q }}"
           placeholder="Search, e.g. GCSE maths" maxlength="100" autocomplete="off">
    {{-- One button submits the search and the filters together. --}}
    <button class="button button--inline" type="submit">Search</button>
  </div>

  <div class="filters__row">
    <label class="filter">
      <span class="filter__label">Subject</span>
      <select class="field field--select" name="category">
        <option value="">Any</option>
        @foreach ($categories->groupBy(fn ($c) => $c->group->heading()) as $group => $inGroup)
          <optgroup label="{{ $group }}">
            @foreach ($inGroup as $category)
              <option value="{{ $category->slug }}" @selected($filters->category?->is($category))>{{ $category->name }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
    </label>

    <label class="filter">
      <span class="filter__label">Age</span>
      <select class="field field--select" name="age">
        <option value="">Any</option>
        @foreach ($ages as $age)
          <option value="{{ $age->value }}" @selected($filters->age === $age)>{{ $age->label() }}</option>
        @endforeach
      </select>
    </label>

    <label class="filter">
      <span class="filter__label">Cost</span>
      <select class="field field--select" name="cost">
        <option value="">Any</option>
        @foreach ($tiers as $tier)
          <option value="{{ $tier->value }}" @selected($filters->cost === $tier)>{{ $tier->label() }}</option>
        @endforeach
      </select>
    </label>

    <label class="filter">
      <span class="filter__label">Where</span>
      <select class="field field--select" name="where">
        <option value="">Anywhere</option>
        <option value="online" @selected($filters->online)>Online or UK-wide only</option>
        @foreach ($nations as $nation)
          @if ($nation->children->isNotEmpty())
            <optgroup label="{{ $nation->name }}">
              <option value="{{ $nation->slug }}" @selected($filters->region?->is($nation))>All of {{ $nation->name }}</option>
              @foreach ($nation->children as $region)
                <option value="{{ $region->slug }}" @selected($filters->region?->is($region))>{{ $region->name }}</option>
              @endforeach
            </optgroup>
          @else
            <option value="{{ $nation->slug }}" @selected($filters->region?->is($nation))>{{ $nation->name }}</option>
          @endif
        @endforeach
      </select>
    </label>

  </div>
</form>
