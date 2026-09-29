<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('brevo', fn (): BrevoTransport => new BrevoTransport(
            http: $this->app->make(HttpFactory::class),
            apiKey: (string) config('services.brevo.key'),
            endpoint: (string) config('services.brevo.endpoint'),
            timeout: (int) config('services.brevo.timeout'),
        ));

        Sanctum::usePersonalAccessTokenModel(
            PersonalAccessToken::class
        );
    }
}
