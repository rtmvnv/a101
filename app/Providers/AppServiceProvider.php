<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\UniOne\UniOne;
use MongoDB\Client;

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

        app()->singleton(Client::class, function () {
            return new Client(
                'mongodb://' . env('MONGODB_USERNAME') . ':' . env('MONGODB_PASSWORD')
                    . '@' . env('MONGODB_SERVER') . ':' . env('MONGODB_PORT')
                    . '/?authSource=' . env('MONGODB_AUTH_SOURCE')
            );
        });

        app()->singleton('mongo_events', function () {
            return (new Client(
                'mongodb://' . env('MONGODB_USERNAME') . ':' . env('MONGODB_PASSWORD')
                    . '@' . env('MONGODB_SERVER') . ':' . env('MONGODB_PORT')
                    . '/?authSource=' . env('MONGODB_AUTH_SOURCE')
            ))->a101->events;
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
