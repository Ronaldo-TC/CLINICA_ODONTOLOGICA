<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class MenuBuilder
{
    /**
     * Catálogo crudo del menú.
     * Cada ítem puede tener:
     *   - type:       'link' | 'label'
     *   - label:      string
     *   - route:      string (nombre de ruta)
     *   - icon:       string (clave en $icons del sidebar)
     *   - pattern:    string (para routeIs, ej. 'pacientes.*')
     *   - permission: string|array  (permiso Spatie requerido)
     *   - role:       string|array  (rol Spatie requerido)
     *   - badge:      string|callable|null
     *   - children:   array (submenú)
     */
    public static function items(): array
    {
        return [
            ['type' => 'label', 'label' => 'Principal'],

            // Dashboard: SIN permiso → todos los autenticados lo ven
            [
                'type'  => 'link',
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'grid',
                'pattern' => 'dashboard',
                // sin 'permission' → siempre visible
            ],
            [
                'type'       => 'link',
                'label'      => 'Pacientes',
                'route'      => 'pacientes.index',
                'icon'       => 'users',
                'pattern'    => 'pacientes.*',
                'permission' => 'pacientes.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Admisiones',
                'route'      => 'admisiones.index',
                'icon'       => 'clipboard',
                'pattern'    => 'admisiones.*',
                'permission' => 'admisiones.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Historias clínicas',
                'route'      => 'historias.index',
                'icon'       => 'file-text',
                'pattern'    => 'historias.*',
                'permission' => 'historias.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Tratamientos',
                'route'      => 'tratamientos.index',
                'icon'       => 'activity',
                'pattern'    => 'tratamientos.*',
                'permission' => 'tratamientos.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Remisiones',
                'route'      => 'remisiones.index',
                'icon'       => 'inbox',
                'pattern'    => 'remisiones.*',
                'permission' => 'remisiones.ver',
            ],

            ['type' => 'label', 'label' => 'Operaciones'],

            [
                'type'       => 'link',
                'label'      => 'Insumos',
                'route'      => 'insumos.index',
                'icon'       => 'package',
                'pattern'    => 'insumos.*',
                'exclude'    => 'solicitudes-insumos.*',
                'permission' => 'insumos.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Solicitudes de insumos',
                'route'      => 'solicitudes-insumos.index',
                'icon'       => 'clipboard',
                'pattern'    => 'solicitudes-insumos.*',
                'permission' => 'insumos.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Esterilización',
                'route'      => 'esterilizacion.index',
                'icon'       => 'layers',
                'pattern'    => 'esterilizacion.*',
                'permission' => 'esterilizaciones.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Radiología',
                'route'      => 'radiologia.index',
                'icon'       => 'image',
                'pattern'    => 'radiologia.*',
                'permission' => 'radiologias.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Cajas',
                'route'      => 'cajas.index',
                'icon'       => 'credit-card',
                'pattern'    => 'cajas.*',
                'permission' => 'cajas.ver',
            ],

            ['type' => 'label', 'label' => 'Análisis'],

            [
                'type'       => 'link',
                'label'      => 'Kardex',
                'route'      => 'kardex.index',
                'icon'       => 'clipboard',
                'pattern'    => 'kardex.*',
                'permission' => 'reportes.ver',
            ],

            [
                'type'       => 'link',
                'label'      => 'Reportes',
                'route'      => 'reportes.index',
                'icon'       => 'chart',
                'pattern'    => 'reportes.*',
                'permission' => 'reportes.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Investigación',
                'route'      => 'investigacion.index',
                'icon'       => 'trending',
                'pattern'    => 'investigacion.*',
                'permission' => 'investigacion.ver',
            ],

            ['type' => 'label', 'label' => 'Administración'],

            // Submenú: Seguridad → Usuarios / Roles
            [
                'type'       => 'link',
                'label'      => 'Seguridad',
                'icon'       => 'shield',
                'permission' => ['usuarios.ver', 'roles.ver'],   // visible si tiene AL MENOS uno
                'children'   => [
                    [
                        'label'      => 'Usuarios',
                        'route'      => 'usuarios.index',
                        'icon'       => 'user-plus',
                        'pattern'    => 'usuarios.*',
                        'permission' => 'usuarios.ver',
                    ],
                    [
                        'label'      => 'Roles y permisos',
                        'route'      => 'roles.index',
                        'icon'       => 'key',
                        'pattern'    => 'roles.*',
                        'permission' => 'roles.ver',
                    ],
                ],
            ],
            [
                'type'       => 'link',
                'label'      => 'Clínicas',
                'route'      => 'clinicas.index',
                'icon'       => 'hospital',
                'pattern'    => 'clinicas.*',
                'permission' => 'clinicas.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Periodos',
                'route'      => 'periodos.index',
                'icon'       => 'folder',
                'pattern'    => 'periodos.*',
                'permission' => 'periodos.ver',
            ],
            [
                'type'       => 'link',
                'label'      => 'Bitácoras',
                'route'      => 'bitacoras.index',
                'icon'       => 'book',
                'pattern'    => 'bitacoras.*',
                'permission' => 'bitacoras.ver',
            ],
        ];
    }

    /**
     * Filtra el menú según los permisos/roles del usuario.
     * Devuelve solo lo que el usuario PUEDE VER.
     */
    public static function forUser($user): array
    {
        if (! $user) return [];

        $items = self::items();
        $out   = [];

        foreach ($items as $item) {

            // ---- Labels: se agregan al buffer (se limpian al final) ----
            if (($item['type'] ?? 'link') === 'label') {
                $out[] = $item;
                continue;
            }

            // ---- Filtro por permiso (string o array) ----
            if (! empty($item['permission'])) {
                $perms = (array) $item['permission'];
                $ok    = false;
                foreach ($perms as $p) {
                    if ($user->can($p)) {
                        $ok = true;
                        break;
                    }
                }
                if (! $ok) continue;   // ❌ el usuario no tiene ninguno de los permisos → ocultar
            }

            // ---- Filtro por rol (string o array) ----
            if (! empty($item['role'])) {
                $roles = (array) $item['role'];
                if (! $user->hasAnyRole($roles)) continue;
            }

            // ---- Submenús: filtrar hijos recursivamente ----
            if (! empty($item['children']) && is_array($item['children'])) {
                $children = [];
                foreach ($item['children'] as $child) {
                    if (! empty($child['permission'])) {
                        $cPerms = (array) $child['permission'];
                        $cOk    = false;
                        foreach ($cPerms as $cp) {
                            if ($user->can($cp)) {
                                $cOk = true;
                                break;
                            }
                        }
                        if (! $cOk) continue;
                    }
                    if (! empty($child['role']) && ! $user->hasAnyRole((array) $child['role'])) {
                        continue;
                    }
                    $children[] = $child;
                }
                // Si el padre tenía hijos y todos fueron filtrados → ocultar el padre
                if (empty($children)) continue;
                $item['children'] = $children;
            }

            // ---- Resolver badges (si son callable) ----
            if (isset($item['badge']) && is_callable($item['badge'])) {
                $item['badge'] = (string) call_user_func($item['badge']);
            }

            $out[] = $item;
        }

        // ---- Limpiar labels huérfanos (labels sin hijos debajo) ----
        $final = [];
        foreach ($out as $i => $item) {
            if (($item['type'] ?? 'link') !== 'label') {
                $final[] = $item;
                continue;
            }
            // ¿Hay algún link después de este label?
            $tieneHijos = false;
            for ($j = $i + 1; $j < count($out); $j++) {
                if (($out[$j]['type'] ?? 'link') === 'label') break;
                $tieneHijos = true;
                break;
            }
            if ($tieneHijos) $final[] = $item;
        }

        return $final;
    }
}
