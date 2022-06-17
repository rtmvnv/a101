<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\UniOne\UniOne;
use MongoDB\Client;
use orangedata\orangedata_client;

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

        app()->singleton(orangedata_client::class, function () {
            return new orangedata_client([
                'inn' => config('services.orangedata.inn'),
                'api_url' => config('services.orangedata.url'),
                'sign_pkey' => storage_path('app/orangedata/private_key.pem'),
                'ssl_client_key' => storage_path('app/orangedata/client.key'),
                'ssl_client_crt' => storage_path('app/orangedata/client.crt'),
                'ssl_ca_cert' => storage_path('app/orangedata/cacert.pem'),
                'ssl_client_crt_pass' => config('services.orangedata.pass'),
            ]);
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
