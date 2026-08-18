<?php

namespace App\Providers;

use App\Contracts\WorkflowGateway;
use App\Services\Workflow\ConfiguredWorkflowRoleResolver;
use App\Services\Workflow\UspdevWorkflowGateway;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Uspdev\Workflow\Contracts\RoleResolver;
use Uspdev\Workflow\WorkflowService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RoleResolver::class, ConfiguredWorkflowRoleResolver::class);

        $this->app->singleton(WorkflowGateway::class, function ($app): WorkflowGateway {
            return new UspdevWorkflowGateway(
                $app->make(WorkflowService::class),
                (string) config('equivalencia.workflow.name', 'equivalencia'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('notLast', function ($expression) {
            return "<?php if (isset(\$loop) && !\$loop->last) { echo {$expression}; } ?>";
        });

        Blade::directive('limitarTexto', function ($expression) {
            return "<?php
                        \$texto = {$expression};

                        echo mb_strlen(\$texto) > 45
                            ? preg_replace('/^(.{16}).+(.{28})$/su', '$1...$2', \$texto)
                            : \$texto;
                    ?>";
        });

    }
}
