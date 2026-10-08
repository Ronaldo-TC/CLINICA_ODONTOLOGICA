<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function autorizarApi(Request $request, string $permiso): void
    {
        $usuario = $request->user();
        abort_unless($usuario && ($usuario->hasRole('admin') || $usuario->can($permiso)), 403, "No tienes permiso {$permiso}");
    }

    /** @return array{total: int, por_pagina: int, pagina: int, ultima_pagina: int} */
    protected function paginacionApi(LengthAwarePaginator $paginador): array
    {
        return [
            'total' => $paginador->total(),
            'por_pagina' => $paginador->perPage(),
            'pagina' => $paginador->currentPage(),
            'ultima_pagina' => $paginador->lastPage(),
        ];
    }

    protected function porPaginaApi(Request $request): int
    {
        return max(1, min($request->integer('por_pagina', 20), 100));
    }
}
