<?php

namespace WalletApi\Configuration;

use Illuminate\Support\ServiceProvider;
use WalletApi\Configuration\Http\Controllers\WalletApiController;
use Illuminate\Support\Facades\DB;

// use 

class WalletApiConfigurationServiceProvider extends ServiceProvider {

    public function boot()
    {
 

        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        // $this->loadViewsFrom(__DIR__.'/views', 'contact');
        // $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        if (\File::exists(__DIR__ . '/Helper.php')) {
            require __DIR__ . '/Helper.php';
        }

        $this->app->singleton('walletapi-configuration', function () {
            return new AliceController(); // Replace with the actual class from your package
        });
        $this->app['router']->aliasMiddleware('api_auth', Http\Middleware\ApiAuthencation::class);


        $this->app->alias('walletapi-configuration', WalletApi\Configuration\WalletApiConfigurationFacade::class);
        $this->mergeConfigFrom(
            __DIR__.'/config/package_constants.php', 'package_constants',
        );
        $this->publishes([
            __DIR__.'/config/package_constants.php' => config_path('package_constants.php'),

            ]);
       
    }
  
    public function register()
    {

    }

}