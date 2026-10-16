<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AsignarRemisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'director', 'admision', 'jefatura-clinicas', 'docente', 'jefe-catedra'])
            && $this->user()->canAny(['remisiones.asignar', 'remisiones.gestionar']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'estudiante_id' => ['required', 'integer', 'exists:usuarios,id'],
            'docente_id' => [$this->docenteActual() ? 'nullable' : 'required', 'integer', 'exists:usuarios,id'],
            'periodo_id' => ['nullable', 'integer', 'exists:periodos,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'estudiante_id.required' => 'Seleccione un estudiante o interno.',
            'estudiante_id.exists' => 'Seleccione un estudiante o interno válido.',
            'docente_id.required' => 'Seleccione el docente responsable.',
            'docente_id.exists' => 'Seleccione un docente válido.',
            'periodo_id.exists' => 'Seleccione un periodo válido.',
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $docente = $this->docenteActual();
            if ($docente && $this->filled('docente_id') && $this->integer('docente_id') !== $docente->id) {
                $validator->errors()->add('docente_id', 'El docente responsable debe ser quien realiza la asignación.');
            }
        }];
    }

    public function docenteActual(): ?Usuario
    {
        $usuario = $this->user();

        return ! $usuario->hasAnyRole(['admin', 'director']) && $usuario->hasAnyRole(['docente', 'jefe-catedra']) ? $usuario : null;
    }

    public function docenteResponsableId(): int
    {
        return $this->docenteActual()?->id ?? $this->integer('docente_id');
    }
}
