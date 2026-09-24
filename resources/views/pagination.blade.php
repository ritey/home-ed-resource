@if ($paginator->hasPages())
  <nav class="pager" aria-label="Pages">
    @if ($paginator->onFirstPage())
      <span class="pager__link pager__link--off" aria-hidden="true">Previous</span>
    @else
      <a class="pager__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
    @endif

    <span class="pager__status">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

    @if ($paginator->hasMorePages())
      <a class="pager__link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
    @else
      <span class="pager__link pager__link--off" aria-hidden="true">Next</span>
    @endif
  </nav>
@endif
