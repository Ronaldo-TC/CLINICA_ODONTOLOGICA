@if ($paginator->total() > 0)
<nav class="table-pagination" role="navigation" aria-label="Paginación de la tabla">
    <p class="table-pagination__summary">
        Mostrando <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong>
        de <strong>{{ $paginator->total() }}</strong> registros
        <span class="table-pagination__page-status">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
    </p>

    <div class="table-pagination__controls">
        @if ($paginator->onFirstPage())
        <span class="table-pagination__link table-pagination__direction" aria-disabled="true" aria-label="Página anterior">
        @else
        <a class="table-pagination__link table-pagination__direction" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">
        @endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            Anterior
        @if ($paginator->onFirstPage())
        </span>
        @else
        </a>
        @endif

        <ul class="table-pagination__pages" aria-label="Páginas disponibles">
            @foreach ($elements as $element)
                @if (is_string($element))
                <li class="table-pagination__separator"><span class="table-pagination__ellipsis" aria-hidden="true">{{ $element }}</span></li>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                    <li @class(['table-pagination__page', 'is-nearby' => abs($page - $paginator->currentPage()) <= 1])>
                        @if ($page === $paginator->currentPage())
                        <span class="table-pagination__link is-current" aria-current="page" aria-label="Página {{ $page }}, página actual">{{ $page }}</span>
                        @else
                        <a class="table-pagination__link" href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
        <a class="table-pagination__link table-pagination__direction" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">
        @else
        <span class="table-pagination__link table-pagination__direction" aria-disabled="true" aria-label="Página siguiente">
        @endif
            Siguiente
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
        @if ($paginator->hasMorePages())
        </a>
        @else
        </span>
        @endif
    </div>
</nav>
@endif
