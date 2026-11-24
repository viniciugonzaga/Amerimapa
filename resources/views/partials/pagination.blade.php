@if ($paginator->hasPages())
  <nav class="row between" style="margin-top:14px" aria-label="Paginação">
    <span class="small muted">Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
    <div class="row">
      @if ($paginator->onFirstPage())<span class="btn btn-sm btn-outline" aria-disabled="true">Anterior</span>@else<a class="btn btn-sm btn-outline" href="{{ $paginator->previousPageUrl() }}">Anterior</a>@endif
      <span class="small bold">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
      @if ($paginator->hasMorePages())<a class="btn btn-sm btn-outline" href="{{ $paginator->nextPageUrl() }}">Próxima</a>@else<span class="btn btn-sm btn-outline" aria-disabled="true">Próxima</span>@endif
    </div>
  </nav>
@endif
