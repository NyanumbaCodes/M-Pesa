<?php


namespace NyanumbaCodes\Mpesa\Providers;

use Illuminate\Support\ServiceProvider;

class MpesaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/mpesa.php', 'mpesa');

        $this->app->singleton('mpesa', function ($app) {
            return new \NyanumbaCodes\Mpesa\Mpesa(config('mpesa'));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/mpesa.php' => config_path('mpesa.php'),
        ], 'mpesa-config');


        if ($this->app->runningInConsole()) {
            $this->commands([
                \NyanumbaCodes\Mpesa\Console\MpesaInstallCommand::class,
            ]);
        }
    }
}
