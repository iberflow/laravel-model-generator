<?php

namespace Iber\Generator;

use Illuminate\Support\ServiceProvider;

class ModelGeneratorProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('command.make.models', function ($app) {
            return $app['Iber\Generator\Commands\MakeModelsCommand'];
        });

        $this->commands('command.make.models');
    }
}
