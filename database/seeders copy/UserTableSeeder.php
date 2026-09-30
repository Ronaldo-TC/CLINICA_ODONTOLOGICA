<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    public function run(): void
    {
        // sha256('123')
        $pwd = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3';
        $now = now()->timestamp;

        // ✅ admin PRIMERO (usernumber = 1)
        $users = [
            ['usernumber' => 1, 'userci' => '1001', 'username' => 'admin',         'userfullname' => 'Administrador del Sistema', 'userdesc' => 'Acceso total',           'usertype' => 'admin'],
            ['usernumber' => 2, 'userci' => '1002', 'username' => 'docente',       'userfullname' => 'Dr. Juan Pérez',           'userdesc' => 'Docente Ortodoncia',     'usertype' => 'teacher'],
            ['usernumber' => 3, 'userci' => '1003', 'username' => 'docente2',      'userfullname' => 'Dra. Carla Méndez',        'userdesc' => 'Docente Endodoncia',     'usertype' => 'teacher'],
            ['usernumber' => 4, 'userci' => '1004', 'username' => 'estudiante',    'userfullname' => 'María López',              'userdesc' => 'Estudiante 5to año',     'usertype' => 'student'],
            ['usernumber' => 5, 'userci' => '1005', 'username' => 'estudiante2',   'userfullname' => 'Pedro Quispe',             'userdesc' => 'Estudiante 4to año',     'usertype' => 'student'],
            ['usernumber' => 6, 'userci' => '1006', 'username' => 'estudiante3',   'userfullname' => 'Ana Condori',              'userdesc' => 'Estudiante 5to año',     'usertype' => 'student'],
            ['usernumber' => 7, 'userci' => '1007', 'username' => 'subadmin',      'userfullname' => 'Carlos Ruiz',              'userdesc' => 'Subadministrador',       'usertype' => 'subadmin'],
            ['usernumber' => 8, 'userci' => '1008', 'username' => 'cajas',         'userfullname' => 'Ana Torres',               'userdesc' => 'Cajera turno mañana',    'usertype' => 'cashier'],
            ['usernumber' => 9, 'userci' => '1009', 'username' => 'cajas2',        'userfullname' => 'Luis Rojas',               'userdesc' => 'Cajero turno tarde',     'usertype' => 'cashier'],
            ['usernumber' => 10, 'userci' => '1010', 'username' => 'esterilizacion', 'userfullname' => 'Luis Gómez',               'userdesc' => 'Encargado esterilización', 'usertype' => 'sterilization'],
            ['usernumber' => 11, 'userci' => '1011', 'username' => 'esterilizacion2', 'userfullname' => 'Rosa Mamani',             'userdesc' => 'Auxiliar esterilización', 'usertype' => 'sterilization'],
            ['usernumber' => 12, 'userci' => '1012', 'username' => 'insumos',       'userfullname' => 'Pedro Díaz',               'userdesc' => 'Encargado almacén',      'usertype' => 'supplies'],
            ['usernumber' => 13, 'userci' => '1013', 'username' => 'insumos2',      'userfullname' => 'Elena Vaca',               'userdesc' => 'Auxiliar insumos',       'usertype' => 'supplies'],
            ['usernumber' => 14, 'userci' => '1014', 'username' => 'radiologia',    'userfullname' => 'Sofía Vera',               'userdesc' => 'Técnica radiología',     'usertype' => 'radiology'],
            ['usernumber' => 15, 'userci' => '1015', 'username' => 'radiologia2',   'userfullname' => 'Marco Salinas',            'userdesc' => 'Técnico radiología',     'usertype' => 'radiology'],
        ];

        foreach ($users as $u) {
            DB::table('usertable')->insert(array_merge($u, [
                'userenabled'      => true,
                'userpermitlogin'  => true,
                'usermultilogin'   => false,
                'userpassword'     => $pwd,
                'userip'           => '0.0.0.0/0',
                'userlastlogin'    => null,
                'usersession'      => '',
                'usersessionextra' => '',
                'userlastlogout'   => null,
                'userpermitip'     => '0.0.0.0/0',
                'userinfo'         => $u['userdesc'],
                'updatetime'       => $now,
            ]));
        }
    }
}
