<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        // Cria gate svgrad
        Gate::define('svgrad', function ($user) {
            return $user->hasAnyRole(['svgrad']) || $user->can('admin');
        });

        Gate::define('equivalencias', function ($user) {
            return $user->can('admin')
                || $user->hasAnyRole(['svgrad'])
                || $user->canAny([
                    'senhaunica.estagiario',
                    'senhaunica.docente',
                    'senhaunica.servidor',
                ]);
        });
    }
}
