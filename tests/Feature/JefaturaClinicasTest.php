<?php

namespace Tests\Feature;

use App\Models\Admision;
use App\Models\Clinica;
use App\Models\HistoriaClinica;
use App\Models\Remision;
use App\Models\Tratamiento;
use App\Models\Usuario;
use Database\Seeders\FlujoClinicoPermissionsSeeder;
use Database\Seeders\JefaturaClinicasPermissionsSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RoleHasPermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class JefaturaClinicasTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    /** @return array<string, array{string}> */
    public static function clinicalModules(): array
    {
        $routes = [
            'dashboard', 'clinicas.index', 'periodos.index', 'especialidades.index',
            'tratamientos.index', 'reportes.index', 'kardex.index', 'pacientes.index',
            'admisiones.index', 'remisiones.index', 'historias.index',
            'tratamientos-realizados.index', 'radiologia.index', 'insumos.index',
            'solicitudes-insumos.index', 'esterilizacion.index', 'cajas.index', 'insumos.reposiciones',
        ];

        return array_combine($routes, array_map(fn (string $route): array => [$route], $routes));
    }

    #[DataProvider('clinicalModules')]
    public function test_jefatura_can_open_each_clinical_management_and_supervision_module(string $route): void
    {
        $this->actingAs($this->jefatura())->get(route($route))->assertOk();
    }

    public function test_sidebar_shows_all_jefatura_modules_and_hides_unassigned_modules(): void
    {
        $response = $this->actingAs($this->jefatura())->get(route('auth.profile'))->assertOk();
        preg_match('/<script[^>]*id="navMenuData"[^>]*>(.*?)<\/script>/s', $response->getContent(), $matches);
        $menu = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(array_map(fn (string $route): string => route($route), array_keys(self::clinicalModules())), array_column($menu, 'href'));
    }

    public function test_dashboard_cards_link_to_accessible_modules(): void
    {
        $response = $this->actingAs($this->jefatura())->get(route('dashboard'))->assertOk();

        foreach (['historias.index', 'insumos.index', 'solicitudes-insumos.index', 'admisiones.index', 'radiologia.index'] as $route) {
            $response->assertSee('href="'.route($route).'"', false);
        }
    }

    public function test_management_permissions_alone_keep_patients_and_clinics_visible(): void
    {
        $user = $this->jefatura();
        Role::findByName('jefatura-clinicas', 'web')->revokePermissionTo(['pacientes.ver', 'clinicas.ver']);

        $response = $this->actingAs($user)->get(route('auth.profile'))->assertOk();

        foreach (['pacientes.index', 'clinicas.index'] as $route) {
            $response->assertSee('href="'.route($route).'"', false);
            $this->get(route($route))->assertOk();
        }
    }

    public function test_jefatura_can_enroll_students_in_clinical_specialties(): void
    {
        $user = $this->jefatura();
        $case = $this->clinicalCase();
        $student = $this->clinicalUser('estudiante');

        $this->actingAs($user)->postJson(route('especialidades.store'), [
            'usuario_id' => $student->id, 'clinica_ids' => [$case['clinic']->id], 'rotacion' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('especialidades', [
            'usuario_id' => $student->id, 'clinica_id' => $case['clinic']->id, 'habilitado' => true, 'rotacion' => 1,
        ]);
    }

    public function test_permission_seeders_restore_jefatura_access_without_removing_custom_permissions(): void
    {
        $this->seed([RolesSeeder::class, PermissionsSeeder::class]);
        $role = Role::findByName('jefatura-clinicas', 'web');
        $role->syncPermissions(['bitacoras.ver']);

        foreach ([RoleHasPermissionsSeeder::class, FlujoClinicoPermissionsSeeder::class, JefaturaClinicasPermissionsSeeder::class] as $seeder) {
            $this->seed($seeder);
            $permissionIds = $role->fresh()->permissions->modelKeys();
            $this->seed($seeder);
            $this->assertSame($permissionIds, $role->fresh()->permissions->modelKeys());
            $user = $this->clinicalUser('jefatura-clinicas');
            foreach (['clinicas.ver', 'pacientes.ver', 'admisiones.ver', 'atenciones.ver', 'insumos.ver', 'solicitudes-insumos.ver', 'cajas.ver', 'radiologias.ver', 'esterilizaciones.ver', 'especialidades.gestionar', 'remisiones.asignar', 'bitacoras.ver'] as $permission) {
                $this->assertTrue($user->can($permission), $seeder.': '.$permission);
            }
        }
    }

    public function test_jefatura_can_review_and_assign_referrals_without_its_own_specialty_enrollment(): void
    {
        $user = $this->jefatura();
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $this->actingAs($user)->get(route('remisiones.index', ['buscar' => $case['admission']->paciente->ci]))
            ->assertOk()->assertSee('href="'.route('remisiones.asignacion', $referral->id).'"', false);
        $this->getJson(route('remisiones.tabla', ['buscar' => $case['admission']->paciente->ci]))
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('remisiones.datos', $referral->id))->assertOk();
        $this->get(route('remisiones.asignacion', $referral->id))->assertOk()
            ->assertSee($case['student']->nombre_completo)->assertSee($case['teacher']->nombre_completo);
    }

    public function test_jefatura_assignment_creates_a_history_for_the_selected_student_and_teacher(): void
    {
        $this->freezeTime();
        $user = $this->jefatura();
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $this->actingAs($user)->post(route('remisiones.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
        ])->assertRedirect(route('historias.index'));

        $this->assertDatabaseHas('remisiones', [
            'id' => $referral->id, 'estado' => 'asignada', 'asignado_por' => $user->id,
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'fecha_asignacion' => now(),
        ]);
        $this->assertDatabaseHas('historias_clinicas', [
            'remision_id' => $referral->id, 'admision_id' => $case['admission']->id,
            'paciente_id' => $case['admission']->paciente_id, 'clinica_id' => $case['clinic']->id,
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'estado' => 'revision', 'autorizado' => false,
        ]);
    }

    public function test_jefatura_can_choose_the_period_when_a_referral_has_none(): void
    {
        $user = $this->jefatura();
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case, ['periodo_id' => null]);

        $this->actingAs($user)->get(route('remisiones.asignacion', $referral->id))->assertOk()
            ->assertSee('name="periodo_id"', false);
        $this->post(route('remisiones.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
            'periodo_id' => $case['history']->periodo_id,
        ])->assertRedirect(route('historias.index'));

        $this->assertDatabaseHas('historias_clinicas', ['remision_id' => $referral->id, 'periodo_id' => $case['history']->periodo_id]);
    }

    public function test_referral_assignment_is_hidden_for_inactive_or_already_assigned_referrals(): void
    {
        $user = $this->jefatura();
        $case = $this->clinicalCase();
        $inactive = $this->pendingReferral($case, ['habilitado' => false]);
        $assigned = $this->pendingReferral($case, ['estado' => 'asignada']);
        $withHistory = $this->pendingReferral($case);
        $case['history']->update(['remision_id' => $withHistory->id]);

        $response = $this->actingAs($user)->get(route('remisiones.index', ['buscar' => $case['admission']->paciente->ci]))->assertOk();
        foreach ([$inactive, $assigned, $withHistory] as $referral) {
            $response->assertDontSee('href="'.route('remisiones.asignacion', $referral->id).'"', false);
        }
    }

    public function test_jefatura_cannot_assign_a_student_from_another_specialty(): void
    {
        $user = $this->jefatura();
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);
        $student = $this->clinicalUser('estudiante');

        $this->actingAs($user)->postJson(route('remisiones.asignar', $referral->id), [
            'estudiante_id' => $student->id, 'docente_id' => $case['teacher']->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('estudiante_id');

        $this->assertDatabaseHas('remisiones', ['id' => $referral->id, 'estado' => 'pendiente', 'estudiante_id' => null]);
        $this->assertDatabaseMissing('historias_clinicas', ['remision_id' => $referral->id]);
    }

    public function test_jefatura_without_assignment_permission_cannot_assign_referrals(): void
    {
        $user = $this->jefatura();
        Role::findByName('jefatura-clinicas', 'web')->revokePermissionTo('remisiones.asignar');
        $case = $this->clinicalCase();
        $referral = $this->pendingReferral($case);

        $this->actingAs($user)->get(route('remisiones.index', ['buscar' => $case['admission']->paciente->ci]))
            ->assertOk()->assertDontSee('href="'.route('remisiones.asignacion', $referral->id).'"', false);
        $this->get(route('remisiones.asignacion', $referral->id))->assertForbidden();
        $this->postJson(route('remisiones.asignar', $referral->id), [
            'estudiante_id' => $case['student']->id, 'docente_id' => $case['teacher']->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('historias_clinicas', ['remision_id' => $referral->id]);
    }

    public function test_jefatura_keeps_operational_and_academic_supervision_read_only(): void
    {
        $this->actingAs($user = $this->jefatura());
        foreach (['insumos.store', 'solicitudes-insumos.store', 'esterilizacion.store', 'radiologia.store', 'admisiones.store', 'historias.store', 'usuarios.store', 'roles.store', 'cajas.store'] as $route) {
            $this->postJson(route($route), [])->assertForbidden();
        }
        foreach (['usuarios.index', 'roles.index', 'investigacion.index', 'pacientes-remitidos.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        foreach (['historias.autorizar', 'atenciones.autorizar', 'solicitudes-insumos.autorizar', 'solicitudes-insumos.aprobar', 'solicitudes-insumos.entregar', 'esterilizaciones.autorizar'] as $permission) {
            $this->assertFalse($user->can($permission), $permission);
        }
    }

    private function jefatura(): Usuario
    {
        $this->seed([RolesSeeder::class, PermissionsSeeder::class]);
        Role::findByName('jefatura-clinicas', 'web')->syncPermissions([]);
        $this->seed(JefaturaClinicasPermissionsSeeder::class);

        return $this->clinicalUser('jefatura-clinicas');
    }

    /**
     * @param  array{student: Usuario, teacher: Usuario, clinic: Clinica, admission: Admision, history: HistoriaClinica, treatment: Tratamiento}  $case
     * @param  array<string, mixed>  $overrides
     */
    private function pendingReferral(array $case, array $overrides = []): Remision
    {
        return Remision::create(array_replace([
            'admision_id' => $case['admission']->id, 'paciente_id' => $case['admission']->paciente_id,
            'clinica_id' => $case['clinic']->id, 'periodo_id' => $case['history']->periodo_id,
            'estado' => 'pendiente', 'habilitado' => true,
        ], $overrides));
    }
}
