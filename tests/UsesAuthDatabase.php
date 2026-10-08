<?php

namespace Tests;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

trait UsesAuthDatabase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('ci', 50)->unique();
            $table->string('nombre_usuario', 20)->unique();
            $table->string('nombre_completo', 200);
            $table->string('descripcion', 300)->default('');
            $table->string('tipo', 20)->default('');
            $table->boolean('habilitado')->default(true);
            $table->boolean('permitir_login')->default(true);
            $table->boolean('multilogin')->default(false);
            $table->string('contrasena', 200)->default('');
            $table->string('ip', 300)->nullable();
            $table->string('ip_permitida', 300)->nullable();
            $table->string('info', 300)->default('');
            $table->string('foto', 300)->nullable();
            $table->string('sesion', 100)->default('');
            $table->string('sesion_extra', 100)->default('');
            $table->timestamp('ultimo_login')->nullable();
            $table->timestamp('ultimo_logout')->nullable();
            $table->timestamps();
        });

        Schema::create('sesiones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('token', 255)->unique();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('inicio');
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('fin')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        foreach (['roles', 'permissions'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->string('name');
                $table->string('descripcion')->default('');
                $table->string('guard_name');
                if ($tableName === 'roles') {
                    $table->boolean('habilitado')->default(true);
                }
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        foreach (['roles' => 'role_id', 'permissions' => 'permission_id'] as $tableName => $foreignKey) {
            Schema::create('model_has_'.$tableName, function (Blueprint $table) use ($tableName, $foreignKey): void {
                $table->foreignId($foreignKey)->constrained($tableName)->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary([$foreignKey, 'model_id', 'model_type']);
            });
        }

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('bitacoras', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('ip', 20);
            $table->timestamp('fecha');
            $table->string('tipo', 20);
            $table->text('datos');
            $table->string('estado', 20)->default('');
            $table->timestamps();
        });

        Schema::create('notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->boolean('leida')->default(false);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param array<string, mixed> $overrides */
    private function createUser(array $overrides = []): Usuario
    {
        $userId = DB::table('usuarios')->insertGetId(array_merge([
            'ci' => 'auth-test-ci',
            'nombre_usuario' => 'usuario_prueba',
            'nombre_completo' => 'Usuario de Prueba',
            'contrasena' => Hash::make('clave-anterior'),
        ], $overrides));

        return Usuario::query()->findOrFail($userId);
    }

    /** @return array{user_id: int, session_token: string} */
    private function createActiveSession(Usuario $user, string $token): array
    {
        $this->insertSession($user, $token);

        return ['user_id' => $user->id, 'session_token' => $token];
    }

    private function insertSession(Usuario $user, string $token): int
    {
        return DB::table('sesiones')->insertGetId([
            'usuario_id' => $user->id,
            'token' => $token,
            'inicio' => now(),
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
