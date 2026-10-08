<?php

namespace App\Http\Controllers;

use App\Models\Admision;
use App\Models\HistoriaClinica;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Services\AccesoClinico;
use App\Services\ImagenOdontograma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OdontogramaController extends Controller
{
    /**
     * Devuelve el odontograma completo listo para Three.js.
     * GET /api/odontogramas/{odontograma}/threejs
     */
    public function threejs(Request $request, Odontograma $odontograma): JsonResponse
    {
        $this->exigirAccesoPaciente($request, $odontograma->paciente_id);
        $odontograma->load(['hallazgos', 'paciente']);

        return response()->json([
            'success' => true,
            'data' => $odontograma->toThreeJsPayload(),
        ]);
    }

    /**
     * Último odontograma de un paciente (el más reciente).
     * GET /api/pacientes/{paciente}/odontograma-actual
     */
    public function actualPorPaciente(Request $request, Paciente $paciente): JsonResponse
    {
        $this->exigirAccesoPaciente($request, $paciente->id);
        $odontograma = Odontograma::where('paciente_id', $paciente->id)
            ->conHallazgos()
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $odontograma) {
            return response()->json([
                'success' => true,
                'data' => [
                    'hallazgos' => [],
                    'version' => 0,
                    'resumen' => [],
                ],
                'message' => 'Paciente sin odontograma, se mostrará arcada limpia',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $odontograma->toThreeJsPayload(),
        ]);
    }

    /**
     * Lista de odontogramas de un paciente (para historial).
     * GET /api/pacientes/{paciente}/odontogramas
     */
    public function indexPorPaciente(Request $request, Paciente $paciente): JsonResponse
    {
        $this->exigirAccesoPaciente($request, $paciente->id);
        $odontogramas = Odontograma::where('paciente_id', $paciente->id)
            ->select(['id', 'version', 'tipo', 'estado', 'created_at'])
            ->withCount('hallazgos')
            ->latest('created_at')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $odontogramas,
        ]);
    }

    public function show(Request $request, Odontograma $odontograma): View
    {
        $this->exigirAccesoPaciente($request, $odontograma->paciente_id);
        $odontograma->load(['hallazgos', 'paciente']);

        return view('odontograma.show', [
            'odontograma' => $odontograma, 'paciente' => $odontograma->paciente,
            'payload' => $odontograma->toThreeJsPayload(),
        ]);
    }

    public function imagen(string $denticion, string $pieza, string $vista, ImagenOdontograma $imagenes): BinaryFileResponse
    {
        $ruta = $imagenes->ruta($denticion, $pieza, $vista);
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta, [
            'Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function guardarHallazgos(Request $request, Odontograma $odontograma): JsonResponse
    {
        $this->exigirAccesoPaciente($request, $odontograma->paciente_id);
        $data = $request->validate([
            'hallazgos' => ['present', 'array', 'max:1000'],
            'hallazgos.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('odontograma_hallazgos', 'id')->where('odontograma_id', $odontograma->id)],
            'hallazgos.*.pieza_dental' => ['required', 'string', 'regex:/^(?:[1-4][1-8]|[5-8][1-5])$/'],
            'hallazgos.*.caras' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[MODVLP]*$/'],
            'hallazgos.*.condicion' => ['required', Rule::in(['caries', 'obturacion', 'ausente', 'corona', 'endodoncia', 'implante', 'sellante', 'fractura', 'movilidad', 'bolsa_periodontal', 'mancha', 'desgaste', 'giroversión', 'diastema', 'supernumerario'])],
            'hallazgos.*.severidad' => ['sometimes', Rule::in(['leve', 'moderada', 'severa'])],
            'hallazgos.*.color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hallazgos.*.movilidad' => ['nullable', 'integer', 'between:0,3'],
            'hallazgos.*.sondaje_mm' => ['nullable', 'numeric', 'between:0,999.9'],
            'hallazgos.*.notas' => ['nullable', 'string', 'max:10000'],
            'layout_3d' => ['sometimes', 'array'],
        ], [
            'hallazgos.*.id.exists' => 'El hallazgo no pertenece a este odontograma.',
            'hallazgos.*.pieza_dental.regex' => 'La pieza dental debe usar una numeración FDI válida.',
            'hallazgos.*.caras.regex' => 'Las caras deben contener únicamente M, O, D, V, L o P.',
            'hallazgos.*.condicion.in' => 'La condición odontológica no es válida.',
            'hallazgos.*.color.regex' => 'El color debe tener el formato hexadecimal #RRGGBB.',
        ]);
        DB::transaction(function () use ($request, $odontograma, $data): void {
            $registro = Odontograma::query()->lockForUpdate()->findOrFail($odontograma->id);
            $existentes = $registro->hallazgos()->lockForUpdate()->get()->keyBy('id');
            $conservados = [];
            foreach ($data['hallazgos'] as $indice => $atributos) {
                $id = $atributos['id'] ?? null;
                unset($atributos['id']);
                if (array_key_exists('caras', $atributos)) {
                    $atributos['caras'] ??= '';
                }
                if ($id) {
                    $hallazgo = $existentes->get($id);
                    if (! $hallazgo) {
                        throw ValidationException::withMessages(['hallazgos.'.$indice.'.id' => 'El hallazgo no pertenece a este odontograma.']);
                    }
                    $hallazgo->update($atributos + ['updated_by' => $request->user()->id]);
                } else {
                    $hallazgo = $registro->hallazgos()->create($atributos + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
                }
                $conservados[] = $hallazgo->id;
            }
            $registro->hallazgos()->whereNotIn('id', $conservados)->delete();
            if (array_key_exists('layout_3d', $data)) {
                $registro->layout_3d = $data['layout_3d'];
            }
            $registro->updated_at = now();
            $registro->save();
        });

        return response()->json(['success' => true, 'data' => $odontograma->fresh()->load(['hallazgos', 'paciente'])->toThreeJsPayload()]);
    }

    private function exigirAccesoPaciente(Request $request, int $pacienteId): void
    {
        $usuario = $request->user();
        if (AccesoClinico::esEstudiante($usuario) || (! AccesoClinico::esAdministrador($usuario) && $usuario->hasAnyRole(['docente', 'jefe-catedra']))) {
            abort_unless(
                AccesoClinico::admisiones(Admision::query(), $usuario)->where('paciente_id', $pacienteId)->exists()
                || AccesoClinico::historias(HistoriaClinica::query(), $usuario)->where('paciente_id', $pacienteId)->exists(),
                404
            );
        }
    }
}
