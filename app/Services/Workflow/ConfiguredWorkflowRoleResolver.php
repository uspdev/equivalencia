<?php

namespace App\Services\Workflow;

use App\Enums\Role;
use Uspdev\Workflow\Contracts\RoleResolver;

final class ConfiguredWorkflowRoleResolver implements RoleResolver
{
    public function exists(string $role): bool
    {
        return Role::tryFrom($role) !== null;
    }
}
