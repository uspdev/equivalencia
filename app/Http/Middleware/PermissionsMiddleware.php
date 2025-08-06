<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        $permissoesAluno = [
            'Alunogr',
            'Alunopos',
            'Alunoceu',
            'Alunoead',
            'Alunopd',
        ];
        $userPermissions = $user->permissions->pluck('name')->toArray();

        if ($user && $userPermissions) {
            foreach ($permissoesAluno as $permissao) {
                if (in_array($permissao, $userPermissions)) {
                    if (!$user->hasRole('aluno')) {
                        $user->assignRole('aluno');
                    }
                    break;
                }
            }
        }

        return $next($request);
    }
}
