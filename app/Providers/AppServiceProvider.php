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
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SSOProviderInterface::class, function (): SSOProviderInterface {
            $driver = strtolower((string) config('services.sso.driver', 'mock'));

            if (in_array($driver, ['keycloak', 'oidc', 'bps'], true)) {
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

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): HtmlString => new HtmlString('
                <link rel="stylesheet" href="' . asset('css/tailwind-compiled.css') . '?v=' . (file_exists(public_path('css/tailwind-compiled.css')) ? filemtime(public_path('css/tailwind-compiled.css')) : time()) . '">
                <link rel="stylesheet" href="' . asset('css/filament-custom.css') . '?v=' . (file_exists(public_path('css/filament-custom.css')) ? filemtime(public_path('css/filament-custom.css')) : time()) . '">
            ')
        );
    }
}
