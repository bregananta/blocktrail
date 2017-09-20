<?php

namespace Bregananta\Blocktrail;

use Illuminate\Support\ServiceProvider;

class BlocktrailServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/main.php' => config_path('blocktrail.php'),
        ]);

        $file = __DIR__ . '/../vendor/autoload.php';

        if (file_exists($file)) {
            require $file;
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('blocktrail', function() {
            return new Blocktrail;
        });
    }
}
