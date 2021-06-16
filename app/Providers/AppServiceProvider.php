<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\UniOne;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton(UniOne::class, function () {
            return new UniOne(config('services.unione.api_key'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
