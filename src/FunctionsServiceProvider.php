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
        //
        $this->publishes([
            __DIR__.'/config/functions.php' => config_path('functions.php'), // 发布配置文件到 laravel 的config 下
        ]);
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
