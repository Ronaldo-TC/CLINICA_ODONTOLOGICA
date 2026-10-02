<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // GET /roles -> muestra tabla
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles')); // tu blade con AJAX
    }

    // POST /roles (AJAX) -> crear rol dinámico
    public function store(Request $r)
    {
        $role = Role::create([
            'name' => $r->name,
            'descripcion' => $r->descripcion,
            'guard_name' => 'web',
            'habilitado' => 1
        ]);
        $role->syncPermissions($r->permisos); // escribe en role_has_permissions
        return response()->json(['success' => true]);
    }

    // POST /roles/{id}/asignar -> asignar rol a usuario
    public function asignar(Request $r, $id)
    {
        $user = \App\Models\Usuario::find($r->usuario_id);
        $user->assignRole($id); // escribe en model_has_roles
        return response()->json(['success' => true]);
    }
}
