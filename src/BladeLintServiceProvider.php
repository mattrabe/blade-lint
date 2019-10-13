<?php

namespace MattRabe\BladeLint;

use Illuminate\Support\ServiceProvider;
use MattRabe\BladeLint\Console\Commands\Lint;

class BladeLintServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register installation command
        $this->commands(Lint::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
