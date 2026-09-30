@if ($bitacoras->hasPages())
<span class="info">
    Mostrando {{ $bitacoras->firstItem() }}–{{ $bitacoras->lastItem() }}
    de {{ $bitacoras->total() }} registros
</span>
<div>
    {{ $bitacoras->withQueryString()->links() }}
</div>
@endif