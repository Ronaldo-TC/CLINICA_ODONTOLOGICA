@if ($historias->hasPages())
<span class="info">
    Mostrando {{ $historias->firstItem() }}–{{ $historias->lastItem() }}
    de {{ $historias->total() }} registros
</span>
<div>
    {{ $historias->withQueryString()->links() }}
</div>
@endif