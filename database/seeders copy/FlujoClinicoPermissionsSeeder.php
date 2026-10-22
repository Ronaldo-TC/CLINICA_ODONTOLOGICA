<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlujoClinicoPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matrix = [
            'admision' => ['remisiones.asignar', 'remisiones.editar'],
            'jefatura-clinicas' => ['especialidades.ver', 'especialidades.gestionar', 'remisiones.ver', 'remisiones.asignar'],
            'subadmin' => ['atenciones.gestionar', 'atenciones.ver', 'admisiones.gestionar', 'especialidades.ver', 'especialidades.gestionar'],
            'docente' => ['remisiones.ver', 'remisiones.asignar', 'admisiones.ver', 'admisiones.autorizar', 'atenciones.autorizar', 'atenciones.ver', 'historias.autorizar', 'solicitudes-insumos.autorizar', 'solicitudes-insumos.rechazar', 'esterilizaciones.autorizar'],
            'jefe-catedra' => ['remisiones.ver', 'remisiones.asignar', 'admisiones.ver', 'admisiones.autorizar', 'tratamientos.ver', 'atenciones.autorizar', 'atenciones.ver', 'historias.autorizar', 'insumos.ver', 'solicitudes-insumos.ver', 'solicitudes-insumos.autorizar', 'solicitudes-insumos.rechazar', 'esterilizaciones.autorizar'],
            'estudiante' => ['admisiones.ver', 'admisiones.crear', 'admisiones.editar', 'tratamientos.ver', 'atenciones.ver', 'atenciones.crear', 'atenciones.editar', 'solicitudes-insumos.consumir'],
            'interno' => ['admisiones.ver', 'admisiones.crear', 'admisiones.editar', 'tratamientos.ver', 'atenciones.ver', 'atenciones.crear', 'atenciones.editar', 'solicitudes-insumos.consumir'],
            'insumos' => ['solicitudes-insumos.entregar', 'solicitudes-insumos.consumir', 'solicitudes-insumos.aprobar', 'solicitudes-insumos.rechazar', 'tratamientos.ver', 'tratamientos.insumos'],
            'cajas' => ['tratamientos.ver', 'tratamientos.precios'],
            'kardex' => ['reportes.ver'],
        ];
        $names = collect($matrix)->flatten()->unique()->push('especialidades.ver', 'especialidades.gestionar');
        DB::table('permissions')->insertOrIgnore($names->map(fn (string $name): array => [
            'name' => $name, 'descripcion' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ])->all());
        $permissions = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');
        $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        DB::table('role_has_permissions')->whereIn('role_id', collect(['estudiante', 'interno'])->map(fn (string $role) => $roles[$role] ?? 0))->whereIn('permission_id', collect(['tratamientos.crear', 'tratamientos.editar'])->map(fn (string $permission) => $permissions[$permission] ?? 0))->delete();
        $matrix['admin'] = $permissions->keys()->all();
        $assignments = [];
        foreach ($matrix as $role => $codes) {
            if (! isset($roles[$role])) {
                continue;
            }
            foreach ($codes as $code) {
                $assignments[] = ['role_id' => $roles[$role], 'permission_id' => $permissions[$code]];
            }
        }
        DB::table('role_has_permissions')->insertOrIgnore($assignments);
        $this->call(SolicitudesInsumosPermissionsSeeder::class);
        $this->call(JefaturaClinicasPermissionsSeeder::class);
    }
}
