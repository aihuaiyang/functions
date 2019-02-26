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

    public function provides(){
        return ['functions'];
    }
}
