<?php

namespace Tests\Feature\Api;

use App\Models\Insumo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * API REST v1 con Laravel Sanctum.
 */
class ApiV1Test extends TestCase
{
    use DatabaseTransactions;

    private function admin(): ?Usuario
    {
        return Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
    }

    /** Inicia sesión por API y devuelve el token. */
    private function token(?Usuario $usuario = null): ?string
    {
        $usuario = $usuario ?? $this->admin();

        if (! $usuario) {
            return null;
        }

        // Se fija una contraseña conocida para la prueba
        $usuario->forceFill(['contrasena' => Hash::make('Secreta123')])->save();

        $res = $this->postJson('/api/v1/login', [
            'nombre_usuario' => $usuario->nombre_usuario,
            'contrasena'     => 'Secreta123',
            'device_name'    => 'phpunit',
        ]);

        $res->assertOk();

        return $res->json('data.access_token');
    }

    public function test_login_devuelve_token_y_usuario(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $admin->forceFill(['contrasena' => Hash::make('Secreta123')])->save();

        $respuesta = $this->postJson('/api/v1/login', [
            'nombre_usuario' => $admin->nombre_usuario,
            'contrasena'     => 'Secreta123',
            'device_name'    => 'pixel-8',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('success', true);
        $respuesta->assertJsonStructure([
            'data' => ['access_token', 'token_type', 'user' => ['id', 'nombre_usuario', 'roles', 'permisos']],
        ]);
        $this->assertSame('Bearer', $respuesta->json('data.token_type'));
        $this->assertNotEmpty($respuesta->json('data.access_token'));
    }

    public function test_login_con_credenciales_invalidas_devuelve_401(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $this->postJson('/api/v1/login', [
            'nombre_usuario' => $admin->nombre_usuario,
            'contrasena'     => 'incorrecta',
        ])->assertStatus(401);
    }

    public function test_los_endpoints_protegidos_exigen_token(): void
    {
        foreach (['/api/v1/me', '/api/v1/dashboard', '/api/v1/pacientes', '/api/v1/notificaciones'] as $url) {
            $this->getJson($url)->assertStatus(401);
        }
    }

    public function test_me_dashboard_y_notificaciones_con_token(): void
    {
        $token = $this->token();

        if (! $token) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $cabeceras = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/v1/me', $cabeceras)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'nombre_usuario', 'roles', 'permisos']]);

        $this->getJson('/api/v1/dashboard', $cabeceras)
            ->assertOk()
            ->assertJsonStructure(['data' => ['tarjetas', 'pacientes_por_mes' => ['labels', 'valores'], 'no_leidas']]);

        $this->getJson('/api/v1/notificaciones', $cabeceras)
            ->assertOk()
            ->assertJsonStructure(['success', 'no_leidas', 'data', 'paginacion' => ['total', 'por_pagina', 'pagina']]);
    }

    public function test_pacientes_e_insumos_devuelven_colecciones_paginadas(): void
    {
        $token = $this->token();

        if (! $token) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $cabeceras = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/v1/pacientes', $cabeceras)
            ->assertOk()
            ->assertJsonStructure(['success', 'data', 'paginacion' => ['total', 'pagina', 'ultima_pagina']]);

        $this->getJson('/api/v1/insumos', $cabeceras)
            ->assertOk()
            ->assertJsonStructure(['success', 'data', 'paginacion']);

        $this->getJson('/api/v1/historias', $cabeceras)
            ->assertOk()
            ->assertJsonStructure(['success', 'data', 'paginacion']);
    }

    public function test_crear_solicitud_por_api(): void
    {
        $token = $this->token();

        if (! $token) {
            $this->markTestSkipped('No hay usuario admin');
        }

        // Se necesita un insumo con su clínica y tipo
        $insumo = Insumo::query()->first();

        if (! $insumo) {
            $this->markTestSkipped('No hay insumos en la base');
        }

        $respuesta = $this->postJson('/api/v1/solicitudes-insumos', [
            'clinica_id'            => $insumo->clinica_id,
            'insumo_tratamiento_id' => $insumo->insumo_tratamiento_id,
            'pieza'                 => 11,
            'detalles'              => [
                ['insumo_id' => $insumo->id, 'cantidad_solicitada' => 1],
            ],
        ], ['Authorization' => "Bearer {$token}"]);

        $respuesta->assertStatus(201);
        $respuesta->assertJsonPath('success', true);
        $respuesta->assertJsonStructure(['data' => ['id', 'estado', 'detalles']]);
    }

    public function test_crear_solicitud_valida_los_datos(): void
    {
        $token = $this->token();

        if (! $token) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $this->postJson('/api/v1/solicitudes-insumos', [
            'clinica_id' => 999999,
            'detalles'   => [],
        ], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_logout_revoca_el_token(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $token = $this->token($admin);
        $this->assertNotEmpty($token);

        $antes = \Laravel\Sanctum\PersonalAccessToken::query()
            ->where('tokenable_id', $admin->id)
            ->count();
        $this->assertGreaterThan(0, $antes);

        $this->postJson('/api/v1/logout', [], ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('success', true);

        // El token se elimina de la base: ya no puede autenticar
        $despues = \Laravel\Sanctum\PersonalAccessToken::query()
            ->where('tokenable_id', $admin->id)
            ->count();

        $this->assertSame(0, $despues, 'El logout debe eliminar el token actual');
    }

    public function test_logout_all_revoca_todos_los_tokens(): void
    {
        $admin = $this->admin();

        if (! $admin) {
            $this->markTestSkipped('No hay usuario admin');
        }

        $admin->forceFill(['contrasena' => Hash::make('Secreta123'), 'multilogin' => true])->save();

        $t1 = $this->postJson('/api/v1/login', [
            'nombre_usuario' => $admin->nombre_usuario,
            'contrasena'     => 'Secreta123',
            'device_name'    => 'dispositivo-1',
        ])->json('data.access_token');

        $t2 = $this->postJson('/api/v1/login', [
            'nombre_usuario' => $admin->nombre_usuario,
            'contrasena'     => 'Secreta123',
            'device_name'    => 'dispositivo-2',
        ])->json('data.access_token');

        $this->assertNotEmpty($t1);
        $this->assertNotEmpty($t2);

        $this->assertSame(2, \Laravel\Sanctum\PersonalAccessToken::query()
            ->where('tokenable_id', $admin->id)
            ->count());

        $this->postJson('/api/v1/logout-all', [], ['Authorization' => "Bearer {$t2}"])
            ->assertOk();

        $this->assertSame(0, \Laravel\Sanctum\PersonalAccessToken::query()
            ->where('tokenable_id', $admin->id)
            ->count(), 'logout-all debe revocar todos los tokens');
    }
}
