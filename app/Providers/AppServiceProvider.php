<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\AuditAuthEventsListener;
use App\Models\LembarKerja;
use App\Models\LkImportProfile;
use App\Models\SystemSetting;
use App\Observers\LembarKerjaObserver;
use App\Observers\LkImportProfileObserver;
use App\Observers\SystemSettingObserver;
use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\Providers\BpsKeycloakOidcProvider;
use App\Services\Auth\Providers\MockSSOProvider;
use Illuminate\Support\Facades\Event;
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
        LembarKerja::observe(LembarKerjaObserver::class);
        LkImportProfile::observe(LkImportProfileObserver::class);
        SystemSetting::observe(SystemSettingObserver::class);

        Event::subscribe(AuditAuthEventsListener::class);
    }
}
