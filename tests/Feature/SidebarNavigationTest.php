<?php

namespace Tests\Feature;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\UsesAuthDatabase;

class SidebarNavigationTest extends TestCase
{
    use UsesAuthDatabase;

    public function test_sidebar_restores_direct_links_in_configuration_clinical_and_operational_order(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['pacientes.ver', 'admisiones.ver', 'historias.ver', 'tratamientos.ver', 'remisiones.ver',
            'atenciones.ver', 'insumos.ver', 'solicitudes-insumos.ver', 'esterilizaciones.ver', 'radiologias.ver', 'cajas.ver',
            'reportes.ver', 'investigacion.ver', 'especialidades.ver', 'usuarios.ver', 'roles.ver',
            'clinicas.ver', 'periodos.ver', 'bitacoras.ver'] as $permission) {
            $role->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'web']));
        }
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('auth.profile'))->assertOk();
        $menu = $this->navigationIndex($response->getContent());

        $this->assertSame(['Dashboard', 'Seguridad', 'Clínicas', 'Periodos', 'Asignar especialidades',
            'Tratamientos', 'Bitácoras', 'Pacientes', 'Admisiones',
            'Remisiones', 'Pacientes remitidos', 'Historias clínicas', 'Atención y avances', 'Radiología',
            'Insumos', 'Solicitudes de insumos', 'Esterilización', 'Cajas', 'Reposición de insumos'], array_column($menu, 'label'));
        $this->assertSame(['Usuarios', 'Roles y permisos'], array_column($menu[1]['children'], 'label'));
        $this->assertSame(route('cajas.index'), $menu[17]['href']);
        $this->assertSame(route('insumos.reposiciones'), $menu[18]['href']);
        $this->assertSame(1, count(array_filter($menu, fn (array $item): bool => $item['children'] !== [])));
        $response->assertDontSee('data-nav-group-id="nav-group-atencion-clinica"', false)
            ->assertDontSee('data-nav-group-id="nav-group-administracion"', false);
    }

    public function test_sidebar_hides_empty_groups_and_modules_without_permission(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::create(['name' => 'cajas.ver', 'guard_name' => 'web']));

        $response = $this->actingAs($user)->get(route('auth.profile'))->assertOk();
        $menu = $this->navigationIndex($response->getContent());

        $this->assertSame(['Dashboard', 'Cajas'], array_column($menu, 'label'));
        $response->assertDontSee('data-nav-group-id=', false)->assertDontSee('Gestión y seguimiento');
    }

    public function test_student_sidebar_keeps_personal_patient_views_in_clinical_order(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'estudiante', 'guard_name' => 'web']);
        foreach (['historias.ver', 'admisiones.ver', 'pacientes.ver'] as $permission) {
            $role->givePermissionTo(Permission::create(['name' => $permission, 'guard_name' => 'web']));
        }
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('auth.profile'))->assertOk();
        $menu = $this->navigationIndex($response->getContent());

        $this->assertSame(['Dashboard', 'Mis Pacientes Admitidos', 'Pacientes derivados', 'Mis Historias Clínicas', 'Mis inscripciones'], array_column($menu, 'label'));
        $response->assertDontSee('href="'.route('pacientes.index').'"', false)
            ->assertDontSee('href="'.route('admisiones.index').'"', false)
            ->assertDontSee('href="'.route('historias.index').'"', false);
    }

    public function test_inscription_page_highlights_only_its_own_direct_link(): void
    {
        $user = $this->createUser();
        $role = Role::create(['name' => 'docente', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'especialidades.ver', 'guard_name' => 'web']));
        $user->assignRole($role);
        $this->actingAs($user);
        request()->setRouteResolver(fn () => app('router')->getRoutes()->getByName('especialidades.mis-inscripciones'));

        $html = view('layouts.partials.sidebar')->render();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $activeLinks = $xpath->query('//a[@aria-current="page"]');

        $this->assertSame(1, $activeLinks->length);
        $this->assertSame(route('especialidades.mis-inscripciones'), $activeLinks->item(0)->getAttribute('href'));
    }

    /** @return array<int, array<string, mixed>> */
    private function navigationIndex(string $html): array
    {
        preg_match('/<script[^>]*id="navMenuData"[^>]*>(.*?)<\/script>/s', $html, $matches);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
