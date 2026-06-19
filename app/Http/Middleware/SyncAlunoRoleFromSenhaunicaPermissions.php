<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SyncAlunoRoleFromSenhaunicaPermissions
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        $permissoesAluno = [
            'Alunogr',
            'Alunogrusp',
            'Alunopos',
            'Alunoposusp',
            'Alunoceu',
            'Alunoceuusp',
            'Alunoead',
            'Alunoeadusp',
            'Alunopd',
            'Alunopdusp'
        ];

        if (! $user) {
            return $next($request);
        }

        $userPermissions = $user->permissions->pluck('name')->toArray();

        if ($userPermissions) {
            foreach ($permissoesAluno as $permissao) {
                if ($this->hasAlunoPermission($userPermissions, $permissao)) {
                    if (! $user->hasRole(Role::ALUNO->value)) {
                        $user->assignRole(Role::ALUNO->value);
                    }
                    break;
                }
            }
        }

        return $next($request);
    }

    private function hasAlunoPermission(array $userPermissions, string $permission): bool
    {
        foreach ($userPermissions as $userPermission) {
            if ($userPermission === $permission || Str::startsWith($userPermission, $permission . '.')) {
                return true;
            }
        }

        return false;
    }
}
