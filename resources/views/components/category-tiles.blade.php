@props(['heading', 'categories'])
<div class="tiles">
  <h3 class="tiles__heading">{{ $heading }}</h3>
  <ul class="tiles__grid">
    @foreach ($categories as $category)
      <li class="tile">
        <a class="tile__name" href="{{ route('browse.category', $category) }}">{{ $category->name }}</a>
        <span class="tile__count">{{ $category->total }} {{ Str::plural('thing', $category->total) }}</span>
        <p class="tile__blurb">{{ $category->blurb }}</p>
      </li>
    @endforeach
  </ul>
</div>
