<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
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
        $this->registerSafeSenhaunicaGates();
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }

    private function registerSafeSenhaunicaGates(): void
    {
        foreach (User::$permissoesHierarquia as $key => $hierarquiaPerm) {
            Gate::define($hierarquiaPerm, function (User $user) use ($key) {
                if ($user->hasRole(Role::ADMIN->value)) {
                    return true;
                }

                for ($i = 0; $i <= $key; $i++) {
                    if ($this->hasSenhaunicaPermission($user, User::$permissoesHierarquia[$i])) {
                        return true;
                    }
                }

                return false;
            });
        }

        foreach (User::$permissoesVinculo as $vinculoPerm) {
            Gate::define('senhaunica.'.strtolower($vinculoPerm), function (User $user) use ($vinculoPerm) {
                return $this->hasSenhaunicaPermission($user, $vinculoPerm) ?: null;
            });
        }
    }

    private function hasSenhaunicaPermission(User $user, string $permission): bool
    {
        return $user->getAllPermissions()->contains(function ($userPermission) use ($permission) {
            return $userPermission->name === $permission && $userPermission->guard_name === User::$hierarquiaNs;
        });
    }
}
