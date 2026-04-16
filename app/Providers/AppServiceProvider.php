<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
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
