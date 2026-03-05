<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/create'));
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

}
