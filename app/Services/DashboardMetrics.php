<?php

namespace App\Services;

use App\Models\Admision;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\HistoriaClinica;
use App\Models\Insumo;
use App\Models\InsumoSolicitado;
use App\Models\Paciente;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * DashboardMetrics
 *
 * Calcula las métricas del panel principal según el rol del usuario.
 * Cada tarjeta se resuelve con una consulta simple y tolerante a tablas
 * vacías (los módulos pueden no tener datos todavía).
 */
class DashboardMetrics
{
    /**
     * Tarjetas visibles para el usuario autenticado.
     *
     * @return array<int, array{label:string, value:string, hint:?string, tone:string, icon:string, url:?string}>
     */
    public static function tarjetas(Usuario $usuario): array
    {
        $claves = self::clavesPorRol($usuario);
        $tarjetas = [];

        foreach ($claves as $clave) {
            $tarjeta = self::resolver($clave, $usuario);

            if ($tarjeta !== null) {
                $tarjetas[] = $tarjeta;
            }
        }

        return $tarjetas;
    }

    /**
     * Claves de tarjetas según el rol principal (con respaldo por permisos).
     *
     * @return array<int, string>
     */
    private static function clavesPorRol(Usuario $usuario): array
    {
        $mapa = [
            'admin' => ['usuarios_activos', 'roles', 'clinicas', 'pacientes_total', 'ingresos_mes'],
            'subadmin' => ['usuarios_activos', 'roles', 'clinicas', 'pacientes_total'],
            'director' => ['pacientes_total', 'tratamientos_total', 'ingresos_mes', 'historias_pendientes', 'insumos_bajo_stock'],
            'jefatura-clinicas' => ['historias_pendientes', 'insumos_bajo_stock', 'solicitudes_pendientes', 'admisiones_hoy', 'radiologias_hoy'],
            'jefe-catedra' => ['historias_catedra', 'solicitudes_catedra', 'pacientes_catedra', 'tratamientos_catedra'],
            'docente' => ['historias_por_autorizar', 'estudiantes_asignados', 'solicitudes_estudiantes', 'tratamientos_docente'],
            'estudiante' => ['mis_pacientes', 'mis_tratamientos', 'mis_solicitudes', 'historias_pendientes'],
            'interno' => ['mis_pacientes', 'mis_tratamientos', 'mis_solicitudes'],
            'admision' => ['admisiones_hoy', 'pacientes_total', 'mis_admisiones'],
            'cajas' => ['recaudacion_hoy', 'recibos_pendientes', 'ingresos_mes'],
            'insumos' => ['solicitudes_pendientes', 'insumos_bajo_stock', 'insumos_agotados', 'solicitudes_por_entregar'],
            'esterilizacion' => ['ciclos_hoy', 'paquetes_entregados', 'ciclos_mes'],
            'radiologia' => ['radiologias_hoy', 'radiografias_pendientes', 'radiografias_mes'],
            'kardex' => ['pacientes_total', 'tratamientos_total', 'ingresos_mes', 'saldos'],
            'investigacion' => ['cohorte_investigacion', 'pacientes_total', 'tratamientos_total'],
        ];

        foreach ($usuario->getRoleNames() as $rol) {
            if (isset($mapa[$rol])) {
                return $mapa[$rol];
            }
        }

        // Sin rol reconocido: tarjetas mínimas coherentes con sus permisos
        $porDefecto = [];
        if ($usuario->can('pacientes.ver')) {
            $porDefecto[] = 'pacientes_total';
        }
        if ($usuario->can('historias.ver')) {
            $porDefecto[] = 'historias_pendientes';
        }
        if ($usuario->can('insumos.ver')) {
            $porDefecto[] = 'solicitudes_pendientes';
        }

        return $porDefecto !== [] ? $porDefecto : ['pacientes_total'];
    }

    /**
     * Construye una tarjeta concreta.
     *
     * @return array{label:string, value:string, hint:?string, tone:string, icon:string, url:?string}|null
     */
    private static function resolver(string $clave, Usuario $usuario): ?array
    {
        try {
            return match ($clave) {
                'historias_catedra' => self::tarjeta('Historias por autorizar',
                    self::contar(AccesoClinico::historias(HistoriaClinica::query(), $usuario)->where('autorizado', false)),
                    'en tus clínicas habilitadas', 'amber', 'file-text', self::ruta('historias.index')),
                'solicitudes_catedra' => self::tarjeta('Solicitudes pendientes',
                    self::contar(AccesoClinico::solicitudes(InsumoSolicitado::query(), $usuario)->where('estado', InsumoSolicitado::ESTADO_PENDIENTE)),
                    'de tu cátedra', 'amber', 'clipboard', self::ruta('solicitudes-insumos.index')),
                'pacientes_catedra' => self::tarjeta('Pacientes de la cátedra',
                    self::contar(Paciente::query()->whereIn('id', AccesoClinico::historias(HistoriaClinica::query(), $usuario)->select('paciente_id'))
                        ->orWhereIn('id', AccesoClinico::admisiones(Admision::query(), $usuario)->select('paciente_id'))),
                    'en tus clínicas habilitadas', 'teal', 'users', self::ruta('admisiones.index')),
                'tratamientos_catedra' => self::tarjeta('Tratamientos realizados',
                    self::contar(TratamientoRealizado::query()->whereIn('historia_clinica_id',
                        AccesoClinico::historias(HistoriaClinica::query(), $usuario)->select('id'))),
                    'de tu cátedra', 'fuchsia', 'activity', self::ruta('tratamientos-realizados.index')),
                'usuarios_activos' => self::tarjeta(
                    'Usuarios activos',
                    self::contar(Usuario::query()->where('habilitado', true)->where('permitir_login', true)),
                    'con acceso al sistema',
                    'teal',
                    'users',
                    self::ruta('usuarios.index')
                ),
                'roles' => self::tarjeta(
                    'Roles definidos',
                    self::contar(DB::table('roles')),
                    'perfiles de acceso',
                    'fuchsia',
                    'key',
                    self::ruta('roles.index')
                ),
                'clinicas' => self::tarjeta(
                    'Clínicas',
                    self::contar(DB::table('clinicas')),
                    'áreas clínicas activas',
                    'cyan',
                    'hospital',
                    self::ruta('clinicas.index')
                ),
                'pacientes_total' => self::tarjeta(
                    'Pacientes',
                    self::contar(DB::table('pacientes')),
                    'registrados en el sistema',
                    'teal',
                    'users',
                    self::ruta('pacientes.index')
                ),
                'tratamientos_total' => self::tarjeta(
                    'Tratamientos realizados',
                    self::contar(DB::table('tratamientos_realizados')),
                    'registrados en el sistema',
                    'fuchsia',
                    'activity',
                    self::ruta('tratamientos.index')
                ),
                'ingresos_mes' => self::tarjeta(
                    'Ingresos del mes',
                    'Bs '.number_format(self::ingresosDelMes(), 2),
                    now()->translatedFormat('F Y'),
                    'cyan',
                    'credit-card',
                    self::ruta('cajas.index')
                ),
                'recaudacion_hoy' => self::tarjeta(
                    'Recaudación de hoy',
                    'Bs '.number_format(self::recaudacionHoy(), 2),
                    now()->format('d/m/Y'),
                    'cyan',
                    'credit-card',
                    self::ruta('cajas.index')
                ),
                'recibos_pendientes' => self::tarjeta(
                    'Saldos por cobrar',
                    'Bs '.number_format(self::saldosPendientes(), 2),
                    'recibos con saldo',
                    'amber',
                    'receipt',
                    self::ruta('cajas.index')
                ),
                'saldos' => self::tarjeta(
                    'Saldos por cobrar',
                    'Bs '.number_format(self::saldosPendientes(), 2),
                    'total pendiente',
                    'amber',
                    'receipt',
                    self::ruta('cajas.index')
                ),
                'historias_pendientes' => self::tarjeta(
                    'Historias sin autorizar',
                    self::contar(HistoriaClinica::query()->where('autorizado', false)),
                    'requieren revisión',
                    'amber',
                    'file-text',
                    self::ruta('historias.index')
                ),
                'historias_por_autorizar' => self::tarjeta(
                    'Historias por autorizar',
                    self::contar(AccesoClinico::historias(HistoriaClinica::query(), $usuario)->where('autorizado', false)),
                    'asignadas a tu supervisión',
                    'amber',
                    'file-text',
                    self::ruta('historias.index')
                ),
                'estudiantes_asignados' => self::tarjeta(
                    'Estudiantes asignados',
                    self::contar(AccesoClinico::historias(HistoriaClinica::query(), $usuario)->distinct()->count('estudiante_id')),
                    'bajo tu supervisión',
                    'cyan',
                    'users',
                    self::ruta('historias.index')
                ),
                'mis_pacientes' => self::tarjeta(
                    'Mis pacientes',
                    self::contar(HistoriaClinica::query()->where('estudiante_id', $usuario->id)->distinct()->select('paciente_id')),
                    'con historia abierta',
                    'teal',
                    'users',
                    self::ruta('historias.index')
                ),
                'mis_tratamientos' => self::tarjeta(
                    'Mis tratamientos',
                    self::contar(DB::table('tratamientos_realizados')->whereIn(
                        'historia_clinica_id',
                        DB::table('historias_clinicas')->select('id')->where('estudiante_id', $usuario->id)
                    )),
                    'realizados',
                    'fuchsia',
                    'activity',
                    self::ruta('tratamientos.index')
                ),
                'mis_solicitudes' => self::tarjeta(
                    'Mis solicitudes de insumos',
                    self::contar(InsumoSolicitado::query()->where('usuario_id', $usuario->id)),
                    'en todos los estados',
                    'cyan',
                    'clipboard',
                    self::ruta('solicitudes-insumos.index')
                ),
                'mis_admisiones' => self::tarjeta(
                    'Admisiones registradas',
                    self::contar(DB::table('admisiones')->where(fn (Builder $query): Builder => $query
                        ->where('registrado_por', $usuario->id)
                        ->orWhere(fn (Builder $legacy): Builder => $legacy->whereNull('registrado_por')->where('estudiante_id', $usuario->id)))),
                    'por ti',
                    'teal',
                    'clipboard',
                    self::ruta('admisiones.index')
                ),
                'admisiones_hoy' => self::tarjeta(
                    'Admisiones de hoy',
                    self::contar(DB::table('admisiones')->whereDate('created_at', today())),
                    now()->format('d/m/Y'),
                    'teal',
                    'clipboard',
                    self::ruta('admisiones.index')
                ),
                'insumos_bajo_stock' => self::tarjeta(
                    'Insumos bajo stock',
                    self::contar(Insumo::query()->whereColumn('cantidad_numerica', '<=', 'stock_minimo')),
                    'requieren reposición',
                    'amber',
                    'package',
                    self::ruta('insumos.index')
                ),
                'insumos_agotados' => self::tarjeta(
                    'Insumos agotados',
                    self::contar(Insumo::query()->where('cantidad_numerica', '<=', 0)),
                    'sin existencias',
                    'fuchsia',
                    'package',
                    self::ruta('insumos.index')
                ),
                'solicitudes_pendientes' => self::tarjeta(
                    'Solicitudes pendientes',
                    self::contar(InsumoSolicitado::query()->where('estado', InsumoSolicitado::ESTADO_PENDIENTE)),
                    'esperando autorización',
                    'amber',
                    'clipboard',
                    self::ruta('solicitudes-insumos.index')
                ),
                'solicitudes_por_entregar' => self::tarjeta(
                    'Solicitudes por entregar',
                    self::contar(InsumoSolicitado::query()->where('estado', InsumoSolicitado::ESTADO_AUTORIZADO)),
                    'autorizadas, sin entregar',
                    'cyan',
                    'package',
                    self::ruta('solicitudes-insumos.index')
                ),
                'solicitudes_estudiantes' => self::tarjeta(
                    'Solicitudes de mis estudiantes',
                    self::contar(AccesoClinico::solicitudes(InsumoSolicitado::query(), $usuario)),
                    'insumos solicitados',
                    'amber',
                    'clipboard',
                    self::ruta('solicitudes-insumos.index')
                ),
                'tratamientos_docente' => self::tarjeta(
                    'Tratamientos supervisados',
                    self::contar(TratamientoRealizado::query()->whereIn('historia_clinica_id',
                        AccesoClinico::historias(HistoriaClinica::query(), $usuario)->select('id'))),
                    'de tus casos asignados', 'fuchsia', 'activity', self::ruta('tratamientos-realizados.index')
                ),
                'ciclos_hoy' => self::tarjeta(
                    'Ciclos de hoy',
                    self::contar(DB::table('esterilizaciones')->whereDate('created_at', today())),
                    now()->format('d/m/Y'),
                    'teal',
                    'layers',
                    self::ruta('esterilizacion.index')
                ),
                'ciclos_mes' => self::tarjeta(
                    'Ciclos del mes',
                    self::contar(DB::table('esterilizaciones')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)),
                    now()->translatedFormat('F Y'),
                    'cyan',
                    'layers',
                    self::ruta('esterilizacion.index')
                ),
                'paquetes_entregados' => self::tarjeta(
                    'Paquetes entregados',
                    (string) (int) DB::table('esterilizaciones')->sum('cantidad_paquetes'),
                    'acumulado',
                    'fuchsia',
                    'package',
                    self::ruta('esterilizacion.index')
                ),
                'radiologias_hoy' => self::tarjeta(
                    'Radiografías de hoy',
                    self::contar(DB::table('radiologias')->whereDate('created_at', today())),
                    now()->format('d/m/Y'),
                    'teal',
                    'image',
                    self::ruta('radiologia.index')
                ),
                'radiografias_pendientes' => self::tarjeta(
                    'Radiografías pendientes',
                    self::contar(DB::table('radiologias')->whereNotIn('estado', ['entregado', 'finalizado', 'realizado'])),
                    'sin entregar',
                    'amber',
                    'image',
                    self::ruta('radiologia.index')
                ),
                'radiografias_mes' => self::tarjeta(
                    'Radiografías del mes',
                    self::contar(DB::table('radiologias')->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)),
                    now()->translatedFormat('F Y'),
                    'cyan',
                    'image',
                    self::ruta('radiologia.index')
                ),
                'cohorte_investigacion' => self::tarjeta(
                    'Cohorte con consentimiento',
                    self::contar(DB::table('admisiones')->where('consentimiento_investigacion', true)),
                    'disponibles para estudio',
                    'teal',
                    'database',
                    self::ruta('investigacion.index')
                ),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /* ============================================================
       CONSULTAS DE APOYO
       ============================================================ */

    private static function contar($query): string
    {
        $total = is_numeric($query) ? $query : $query->count();

        return number_format((float) $total, 0, ',', '.');
    }

    private static function ingresosDelMes(): float
    {
        return (float) Caja::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('cancelado');
    }

    private static function recaudacionHoy(): float
    {
        return (float) Caja::query()->whereDate('created_at', today())->sum('cancelado');
    }

    private static function saldosPendientes(): float
    {
        return (float) Caja::query()
            ->selectRaw('COALESCE(SUM(costo_total - cancelado), 0) as saldo')
            ->value('saldo');
    }

    private static function tarjeta(
        string $label,
        string $value,
        ?string $hint,
        string $tone,
        string $icon,
        ?string $url
    ): array {
        return compact('label', 'value', 'hint', 'tone', 'icon', 'url');
    }

    private static function ruta(string $nombre): ?string
    {
        return Route::has($nombre) ? route($nombre) : null;
    }

    /* ============================================================
       WIDGETS GLOBALES
       ============================================================ */

    /**
     * Admisiones por mes en los últimos 12 meses.
     *
     * @return array{labels: array<int,string>, valores: array<int,int>}
     */
    public static function pacientesPorMes(?Usuario $usuario = null): array
    {
        $inicio = now()->startOfMonth()->subMonths(11);

        $conteos = DB::table('admisiones')
            ->when($usuario?->hasAnyRole(['docente', 'jefe-catedra']) && ! AccesoClinico::esAdministrador($usuario),
                fn (Builder $query) => $query->whereIn('id', AccesoClinico::admisiones(Admision::query(), $usuario)->select('id')))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
            ->where('created_at', '>=', $inicio)
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $labels = [];
        $valores = [];

        for ($i = 0; $i < 12; $i++) {
            $fecha = $inicio->copy()->addMonths($i);
            $clave = $fecha->format('Y-m');
            $labels[] = ucfirst($fecha->translatedFormat('M y'));
            $valores[] = (int) ($conteos[$clave] ?? 0);
        }

        return ['labels' => $labels, 'valores' => $valores];
    }

    /**
     * Top de especialidades por admisiones.
     *
     * @return array{labels: array<int,string>, valores: array<int,int>}
     */
    public static function topEspecialidades(int $limite = 5, ?Usuario $usuario = null): array
    {
        $filas = DB::table('admisiones')
            ->join('historias_clinicas', 'historias_clinicas.admision_id', '=', 'admisiones.id')
            ->join('clinicas', 'clinicas.id', '=', 'historias_clinicas.clinica_id')
            ->when($usuario?->hasAnyRole(['docente', 'jefe-catedra']) && ! AccesoClinico::esAdministrador($usuario),
                fn (Builder $query) => $query->whereIn('historias_clinicas.id', AccesoClinico::historias(HistoriaClinica::query(), $usuario)->select('id')))
            ->selectRaw('clinicas.especialidad as nombre, COUNT(DISTINCT admisiones.id) as total')
            ->groupBy('clinicas.especialidad')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        return [
            'labels' => $filas->pluck('nombre')->all(),
            'valores' => $filas->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * Últimas entradas de bitácora.
     */
    public static function actividadReciente(Usuario $usuario, int $limite = 8): Collection
    {
        return Bitacora::query()
            ->visiblePara($usuario)
            ->with('usuario:id,nombre_usuario,nombre_completo')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }
}
