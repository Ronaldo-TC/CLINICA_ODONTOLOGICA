<?php

namespace App\Models;

use Database\Factories\EsterilizacionCicloFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsterilizacionCiclo extends Model
{
    /** @use HasFactory<EsterilizacionCicloFactory> */
    use HasFactory;

    protected $table = 'esterilizacion_ciclos';

    protected $fillable = [
        'esterilizacion_id', 'equipo', 'numero_carga', 'protocolo', 'inicio', 'fin',
        'temperatura', 'presion', 'minutos_exposicion', 'codigos_material', 'registrado_por',
        'estado', 'control_fisico', 'control_quimico', 'control_biologico',
        'justificacion_biologico', 'observaciones', 'revisiones', 'revisado_por',
        'fecha_revision', 'liberado_por', 'fecha_liberacion',
        'version_preparacion', 'preparacion',
    ];

    protected $casts = [
        'inicio' => 'datetime', 'fin' => 'datetime', 'fecha_revision' => 'datetime',
        'fecha_liberacion' => 'datetime', 'temperatura' => 'decimal:2', 'presion' => 'decimal:3',
        'minutos_exposicion' => 'decimal:2', 'control_fisico' => 'boolean', 'control_quimico' => 'boolean',
        'codigos_material' => 'array', 'revisiones' => 'array',
        'preparacion' => 'array', 'version_preparacion' => 'integer',
    ];

    public function esterilizacion(): BelongsTo
    {
        return $this->belongsTo(Esterilizacion::class, 'esterilizacion_id');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    public function responsableLiberacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'liberado_por');
    }

    public function controlesConformes(): bool
    {
        return $this->control_fisico && $this->control_quimico
            && ($this->control_biologico === 'conforme'
                || ($this->control_biologico === 'no_aplica' && filled($this->justificacion_biologico)));
    }
}
