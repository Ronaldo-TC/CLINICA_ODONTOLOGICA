<?php

namespace App\Http\Controllers;

use App\Models\HistoriaClinica;
use App\Models\SolicitudRadiologica;
use App\Services\AccesoClinico;
use App\Services\SolicitudRadiologicaWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SolicitudRadiologicaController extends Controller
{
    public function store(Request $request, int $historia, SolicitudRadiologicaWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:150'],
            'costo_autorizado' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
        ]);
        $registro = AccesoClinico::historias(HistoriaClinica::query(), $request->user())->findOrFail($historia);

        $workflow->crear(
            $registro,
            $request->user(),
            $data['descripcion'],
            isset($data['costo_autorizado']) ? (float) $data['costo_autorizado'] : null,
        );

        return back()->with('success', 'La solicitud radiológica fue registrada.');
    }

    public function revisar(Request $request, int $solicitud, SolicitudRadiologicaWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'accion' => ['required', Rule::in(['autorizar', 'observar', 'rechazar'])],
            'observacion' => ['required_if:accion,observar,rechazar', 'nullable', 'string', 'max:2000'],
            'costo_autorizado' => ['required_if:accion,autorizar', 'nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
        ]);
        $registro = AccesoClinico::solicitudesRadiologicas(
            SolicitudRadiologica::query(),
            $request->user(),
        )->findOrFail($solicitud);

        $workflow->revisar(
            $registro,
            $request->user(),
            $data['accion'],
            $data['observacion'] ?? null,
            isset($data['costo_autorizado']) ? (float) $data['costo_autorizado'] : null,
        );

        return back()->with('success', 'La revisión de la solicitud radiológica fue registrada.');
    }

    public function reenviar(Request $request, int $solicitud, SolicitudRadiologicaWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['descripcion' => ['required', 'string', 'max:150']]);
        $registro = AccesoClinico::solicitudesRadiologicas(
            SolicitudRadiologica::query(),
            $request->user(),
        )->findOrFail($solicitud);

        $workflow->reenviar($registro, $request->user(), $data['descripcion']);

        return back()->with('success', 'La solicitud radiológica fue reenviada al docente.');
    }

    public function pagar(Request $request, int $solicitud, SolicitudRadiologicaWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'tipo_pago' => ['nullable', 'string', 'max:100'],
        ]);
        $registro = SolicitudRadiologica::query()->findOrFail($solicitud);

        $recibo = $workflow->registrarPago(
            $registro,
            $request->user(),
            (float) $data['monto'],
            $data['tipo_pago'] ?? null,
        );

        return back()->with('success', 'Pago registrado como recibo #'.$recibo->recibo.'.');
    }

    public function realizar(Request $request, int $solicitud, SolicitudRadiologicaWorkflow $workflow): RedirectResponse
    {
        $registro = SolicitudRadiologica::query()->findOrFail($solicitud);
        $estudio = $workflow->registrarEstudio($registro, $request->user());

        return redirect()->route('radiologia.visor', $estudio)
            ->with('success', 'Estudio registrado; puede cargar las imágenes del resultado.');
    }
}
