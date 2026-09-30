<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckRoleTest extends TestCase
{
    public function test_role_permissions_control_read_and_write_access_to_user_routes(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->boolean('habilitado');
        });
        Schema::create('usuario_rol', function (Blueprint $table): void {
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('rol_id');
        });
        Schema::create('permisos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo');
            $table->string('modulo');
        });
        Schema::create('rol_permiso', function (Blueprint $table): void {
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('permiso_id');
            $table->boolean('habilitado');
        });

        DB::table('roles')->insert([
            ['id' => 10, 'nombre' => 'subadmin', 'habilitado' => true],
            ['id' => 11, 'nombre' => 'admin', 'habilitado' => true],
        ]);
        DB::table('usuario_rol')->insert([
            'usuario_id' => 20,
            'rol_id' => 10,
        ]);
        DB::table('permisos')->insert([
            ['id' => 1, 'codigo' => 'usuarios.ver', 'modulo' => 'usuarios'],
            ['id' => 2, 'codigo' => 'usuarios.gestionar', 'modulo' => 'usuarios'],
        ]);
        DB::table('rol_permiso')->insert([
            'rol_id' => 10,
            'permiso_id' => 1,
            'habilitado' => true,
        ]);

        Route::middleware(['web', 'role'])->get('/admin/usuarios/permission-check', fn() => 'ok');
        Route::middleware(['web', 'role'])->post('/admin/usuarios/permission-check', fn() => 'ok');
        Route::middleware(['web', 'role'])->get('/admin/roles/permission-check', fn() => 'ok');
        $session = ['user_id' => 20, 'user_role' => 'subadmin'];
        $this->withSession($session)->get('/admin/usuarios/permission-check')->assertOk();
        $this->withSession($session)->post('/admin/usuarios/permission-check')->assertRedirect(route('auth.login'));
        $this->withSession($session)->get('/admin/roles/permission-check')->assertRedirect(route('auth.login'));
        $this->withSession($session)->get('/auth/login')->assertRedirect('/subadmin/dashboard');

        DB::table('rol_permiso')->insert([
            'rol_id' => 10,
            'permiso_id' => 2,
            'habilitado' => true,
        ]);
        $this->withSession($session)->post('/admin/usuarios/permission-check')->assertOk();

        DB::table('rol_permiso')->where('permiso_id', 1)->update(['habilitado' => false]);
        $this->withSession($session)->get('/admin/usuarios/permission-check')->assertOk();

        DB::table('rol_permiso')->where('permiso_id', 2)->update(['habilitado' => false]);
        $this->withSession($session)->get('/admin/usuarios/permission-check')->assertRedirect(route('auth.login'));

        DB::table('roles')->where('id', 10)->update(['habilitado' => false]);
        $this->withSession($session)->get('/subadmin/dashboard')->assertRedirect(route('auth.login'));

        $this->withSession(['user_id' => 21, 'user_role' => 'admin'])
            ->get('/admin/roles/permission-check')
            ->assertRedirect(route('auth.login'));
    }
}
