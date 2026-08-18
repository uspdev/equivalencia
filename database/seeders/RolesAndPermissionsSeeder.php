<?php

namespace Database\Seeders;

use App\Enums\Permission as AppPermission;
use App\Enums\Role as AppRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AppRole::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }

        foreach (User::$permissoesHierarquia as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => User::$hierarquiaNs,
            ]);
        }

        $permissions = collect([
            AppPermission::REQUERIMENTOS_CREATE,
            AppPermission::REQUERIMENTOS_VIEW_OWN,
            AppPermission::APROVEITAMENTOS_AUTOMATICOS_VIEW,
            AppPermission::APROVEITAMENTOS_AUTOMATICOS_MANAGE,
        ])->mapWithKeys(function (AppPermission $permission) {
            $model = Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);

            return [$permission->value => $model];
        });

        Role::findByName(AppRole::ALUNO->value, 'web')->givePermissionTo([
            $permissions[AppPermission::REQUERIMENTOS_CREATE->value],
            $permissions[AppPermission::REQUERIMENTOS_VIEW_OWN->value],
        ]);

        Role::findByName(AppRole::SVGRAD->value, 'web')->givePermissionTo([
            $permissions[AppPermission::APROVEITAMENTOS_AUTOMATICOS_VIEW->value],
        ]);

        Role::firstOrCreate(['name' => AppRole::CG->value, 'guard_name' => 'web'])
            ->syncPermissions([
                $permissions[AppPermission::APROVEITAMENTOS_AUTOMATICOS_VIEW->value],
                $permissions[AppPermission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value],
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
