<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\Providers\BpsKeycloakOidcProvider;
use App\Services\Auth\Providers\MockSSOProvider;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SsoVpnResilienceTest extends TestCase
{
    public function test_app_service_provider_resolves_drivers_correctly(): void
    {
        // 1. Default / mock driver
        config(['services.sso.driver' => 'mock']);
        $providerMock = app(SSOProviderInterface::class);
        $this->assertInstanceOf(MockSSOProvider::class, $providerMock);

        // 2. Keycloak driver
        config(['services.sso.driver' => 'keycloak']);
        $providerKeycloak = app(SSOProviderInterface::class);
        $this->assertInstanceOf(BpsKeycloakOidcProvider::class, $providerKeycloak);

        // 3. BPS alias driver
        config(['services.sso.driver' => 'bps']);
        $providerBps = app(SSOProviderInterface::class);
        $this->assertInstanceOf(BpsKeycloakOidcProvider::class, $providerBps);
    }

    public function test_sso_callback_handles_vpn_connection_timeout_gracefully(): void
    {
        $mockProvider = Mockery::mock(SSOProviderInterface::class);
        $mockProvider->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new ConnectException(
                'cURL error 28: Failed to connect to sso.bps.go.id port 443: Connection timed out',
                new Psr7Request('GET', 'https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/token')
            ));

        $this->app->instance(SSOProviderInterface::class, $mockProvider);

        $response = $this->get('/auth/sso/callback?code=some_code&state=some_state');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');

        $errorMessage = session('error');
        $this->assertStringContainsString('FortiClient VPN', $errorMessage);
        $this->assertFalse(Auth::check());
    }

    public function test_sso_callback_handles_runtime_exception_gracefully(): void
    {
        $mockProvider = Mockery::mock(SSOProviderInterface::class);
        $mockProvider->shouldReceive('handleCallback')
            ->once()
            ->andThrow(new RuntimeException('Kode otorisasi dari SSO BPS tidak ditemukan.'));

        $this->app->instance(SSOProviderInterface::class, $mockProvider);

        $response = $this->get('/auth/sso/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Kode otorisasi dari SSO BPS tidak ditemukan.');
        $this->assertFalse(Auth::check());
    }
}

