<?php

namespace Tests;

use App\Enums\Permission as AppPermission;
use App\Enums\Role as AppRole;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function seedBusinessPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'admin',
            'boss',
            'manager',
            'poweruser',
            'user',
        ] as $permission) {
            PermissionModel::firstOrCreate(['name' => $permission, 'guard_name' => 'senhaunica']);
        }

        foreach ([
            AppPermission::REQUERIMENTOS_CREATE,
            AppPermission::REQUERIMENTOS_VIEW_OWN,
            AppPermission::APROVEITAMENTOS_AUTOMATICOS_VIEW,
            AppPermission::APROVEITAMENTOS_AUTOMATICOS_MANAGE,
        ] as $permission) {
            PermissionModel::firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => AppRole::ALUNO->value, 'guard_name' => 'web'])
            ->syncPermissions([
                AppPermission::REQUERIMENTOS_CREATE->value,
                AppPermission::REQUERIMENTOS_VIEW_OWN->value,
            ]);

        Role::firstOrCreate(['name' => AppRole::SVGRAD->value, 'guard_name' => 'web'])
            ->syncPermissions([
                AppPermission::APROVEITAMENTOS_AUTOMATICOS_VIEW->value,
            ]);

        Role::firstOrCreate(['name' => AppRole::CG->value, 'guard_name' => 'web'])
            ->syncPermissions([
                AppPermission::APROVEITAMENTOS_AUTOMATICOS_VIEW->value,
                AppPermission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value,
            ]);

        Role::firstOrCreate(['name' => AppRole::ADMIN->value, 'guard_name' => 'web']);
    }
}
