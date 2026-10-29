<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\Providers\BpsKeycloakOidcProvider;
use App\Services\Auth\Providers\MockSSOProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SSOProviderInterface::class, function (): SSOProviderInterface {
            $driver = (string) config('services.sso.driver', 'mock');

            if ($driver === 'oidc') {
                return new BpsKeycloakOidcProvider;
            }

            return new MockSSOProvider;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
