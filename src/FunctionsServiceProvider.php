<?php

namespace Huaiyang\Functions;

use Illuminate\Support\ServiceProvider;

class FunctionsServiceProvider extends ServiceProvider
{

    protected $defer = true;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //配置文件
        $path = realpath(__DIR__.'/config/functions.php');

        $this->publishes([$path => config_path('functions.php')]);
        $this->mergeConfigFrom($path, 'functions');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('functions', function () {
            return new Functions();
        });
    }

    public function provides()
    {
        return ['functions'];
    }
}
