<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Auth\DTOs\SSOUserData;
use App\Services\Auth\Providers\BpsKeycloakOidcProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TestCase;

class BpsKeycloakOidcProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sso.base_url' => 'https://sso.bps.go.id',
            'services.sso.realm' => 'pegawai-bps',
            'services.sso.client_id' => '13328-elka-nv1',
            'services.sso.client_secret' => 'rahasia123',
            'services.sso.redirect_uri' => 'http://localhost:8000/auth/sso/callback',
            'services.sso.timeout' => 5.0,
        ]);
    }

    public function test_provider_throws_exception_if_client_id_empty_on_redirect(): void
    {
        config(['services.sso.client_id' => '']);
        $provider = new BpsKeycloakOidcProvider();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Client ID SSO BPS belum dikonfigurasi.');

        $provider->getRedirectResponse();
    }

    public function test_provider_builds_correct_keycloak_urls(): void
    {
        $provider = new BpsKeycloakOidcProvider();

        $this->assertSame(
            'https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/auth',
            $provider->getAuthUrl()
        );
        $this->assertSame(
            'https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/token',
            $provider->getTokenUrl()
        );
        $this->assertSame(
            'https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/userinfo',
            $provider->getUserinfoUrl()
        );
        $this->assertSame(
            'https://sso.bps.go.id/auth/realms/pegawai-bps/api-pegawai/username/',
            $provider->getApiPegawaiUrl()
        );
    }

    public function test_provider_generates_redirect_response_with_csrf_state(): void
    {
        $provider = new BpsKeycloakOidcProvider();
        $response = $provider->getRedirectResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $targetUrl = $response->getTargetUrl();

        $this->assertStringContainsString('https://sso.bps.go.id/auth/realms/pegawai-bps/protocol/openid-connect/auth', $targetUrl);
        $this->assertStringContainsString('client_id=13328-elka-nv1', $targetUrl);
        $this->assertStringContainsString('response_type=code', $targetUrl);
        $this->assertStringContainsString('state=', $targetUrl);

        $savedState = session('sso_state');
        $this->assertNotEmpty($savedState);
        $this->assertStringContainsString('state='.$savedState, $targetUrl);
    }

    public function test_provider_throws_exception_if_keycloak_returns_error(): void
    {
        $provider = new BpsKeycloakOidcProvider();
        $request = Request::create('/auth/sso/callback', 'GET', [
            'error' => 'access_denied',
            'error_description' => 'User cancelled login',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Otorisasi SSO BPS dibatalkan atau gagal: User cancelled login');

        $provider->handleCallback($request);
    }

    public function test_provider_throws_exception_if_auth_code_missing(): void
    {
        $provider = new BpsKeycloakOidcProvider();
        $request = Request::create('/auth/sso/callback', 'GET', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kode otorisasi dari SSO BPS tidak ditemukan.');

        $provider->handleCallback($request);
    }

    public function test_provider_throws_exception_if_state_mismatched(): void
    {
        session(['sso_state' => 'state_asli']);

        $provider = new BpsKeycloakOidcProvider();
        $request = Request::create('/auth/sso/callback', 'GET', [
            'code' => 'auth_code_123',
            'state' => 'state_palsu',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('State CSRF SSO BPS tidak cocok.');

        $provider->handleCallback($request);
    }

    public function test_provider_exchanges_code_for_token_and_enriches_from_api_pegawai(): void
    {
        session(['sso_state' => 'valid_state']);

        $mockHandler = new MockHandler([
            // 1. Token response
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'mock_token_abc',
                'token_type' => 'Bearer',
            ]) ?: ''),
            // 2. Userinfo response
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'keycloak-sub-001',
                'email' => 'budi.santoso@bps.go.id',
                'preferred_username' => '199001012015011001',
                'name' => 'Budi Basic',
            ]) ?: ''),
            // 3. API Pegawai response
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                [
                    'nama' => 'Budi Santoso, S.Tr.Stat.',
                    'nip' => '199001012015011001',
                    'jabatan' => 'Statistisi Ahli Pertama',
                ]
            ]) ?: ''),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $provider = new BpsKeycloakOidcProvider($httpClient);

        $request = Request::create('/auth/sso/callback', 'GET', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $userData = $provider->handleCallback($request);

        $this->assertInstanceOf(SSOUserData::class, $userData);
        $this->assertSame('keycloak-sub-001', $userData->id);
        $this->assertSame('budi.santoso@bps.go.id', $userData->email);
        $this->assertSame('Budi Santoso, S.Tr.Stat.', $userData->name);
        $this->assertSame('199001012015011001', $userData->nip);
        $this->assertArrayHasKey('pegawai_data', $userData->rawAttributes);
    }

    public function test_provider_degrades_gracefully_if_api_pegawai_fails(): void
    {
        session(['sso_state' => 'valid_state']);

        $mockHandler = new MockHandler([
            // 1. Token response
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'mock_token_xyz',
            ]) ?: ''),
            // 2. Userinfo response
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'keycloak-sub-002',
                'email' => 'siti.aminah@bps.go.id',
                'preferred_username' => '199201012018012002',
                'name' => 'Siti Aminah',
            ]) ?: ''),
            // 3. API Pegawai error 500
            new GuzzleResponse(500, [], 'Internal Server Error'),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $provider = new BpsKeycloakOidcProvider($httpClient);

        $request = Request::create('/auth/sso/callback', 'GET', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $userData = $provider->handleCallback($request);

        $this->assertInstanceOf(SSOUserData::class, $userData);
        $this->assertSame('keycloak-sub-002', $userData->id);
        $this->assertSame('siti.aminah@bps.go.id', $userData->email);
        $this->assertSame('Siti Aminah', $userData->name);
        $this->assertSame('199201012018012002', $userData->nip);
    }

    public function test_provider_throws_exception_if_email_is_completely_missing(): void
    {
        session(['sso_state' => 'valid_state']);

        $mockHandler = new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'mock_token_no_email',
            ]) ?: ''),
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'keycloak-sub-003',
                'name' => 'Tanpa Email',
            ]) ?: ''),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $provider = new BpsKeycloakOidcProvider($httpClient);

        $request = Request::create('/auth/sso/callback', 'GET', [
            'code' => 'valid_code',
            'state' => 'valid_state',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Alamat email tidak ditemukan pada profil SSO BPS Anda.');

        $provider->handleCallback($request);
    }
}

