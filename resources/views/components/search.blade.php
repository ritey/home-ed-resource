@props(['value' => ''])
<form class="search" action="{{ route('browse') }}" method="get" role="search">
  <label class="visually-hidden" for="search-q">Search the list</label>
  <input class="field field--search" type="search" id="search-q" name="q" value="{{ $value }}"
         placeholder="Search, e.g. GCSE maths" maxlength="100" autocomplete="off">
  <button class="button button--inline" type="submit">Search</button>
</form>
