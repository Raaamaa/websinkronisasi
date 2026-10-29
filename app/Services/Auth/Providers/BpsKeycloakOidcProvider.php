<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\DTOs\SSOUserData;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class BpsKeycloakOidcProvider implements SSOProviderInterface
{
    private Client $httpClient;

    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private string $authUrl;

    private string $tokenUrl;

    private string $userinfoUrl;

    public function __construct(?Client $client = null)
    {
        $this->httpClient = $client ?? new Client(['timeout' => 15.0]);
        $this->clientId = (string) config('services.sso.client_id', '');
        $this->clientSecret = (string) config('services.sso.client_secret', '');
        $this->redirectUri = (string) config('services.sso.redirect_uri', '');

        $baseUrl = rtrim((string) config('services.sso.base_url', 'https://sso.bps.go.id'), '/');
        $realm = (string) config('services.sso.realm', 'pegawai-bps');

        $this->authUrl = $baseUrl."/auth/realms/{$realm}/protocol/openid-connect/auth";
        $this->tokenUrl = $baseUrl."/auth/realms/{$realm}/protocol/openid-connect/token";
        $this->userinfoUrl = $baseUrl."/auth/realms/{$realm}/protocol/openid-connect/userinfo";
    }

    /**
     * Redirect user to BPS Keycloak Authorization Page.
     */
    public function getRedirectResponse(): Response
    {
        $state = bin2hex(random_bytes(16));
        session(['sso_state' => $state]);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email profile-pegawai',
            'state' => $state,
        ]);

        return new RedirectResponse($this->authUrl.'?'.$query);
    }

    /**
     * Exchange authorization code for token and fetch BPS employee profile.
     */
    public function handleCallback(Request $request): SSOUserData
    {
        $code = (string) $request->input('code', '');
        $state = (string) $request->input('state', '');
        $expectedState = (string) session('sso_state', '');

        if ($code === '') {
            throw new RuntimeException('Kode otorisasi dari SSO BPS tidak ditemukan.');
        }

        if ($expectedState !== '' && $state !== $expectedState) {
            throw new RuntimeException('State CSRF SSO BPS tidak cocok.');
        }

        // 1. Exchange authorization code for tokens
        $response = $this->httpClient->post($this->tokenUrl, [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $tokenData = json_decode((string) $response->getBody(), true);
        $accessToken = $tokenData['access_token'] ?? null;

        if (! $accessToken) {
            throw new RuntimeException('Gagal mendapatkan access token dari SSO BPS.');
        }

        // 2. Fetch Userinfo from BPS Keycloak
        $userinfoResponse = $this->httpClient->get($this->userinfoUrl, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        $profile = json_decode((string) $userinfoResponse->getBody(), true);

        return new SSOUserData(
            id: (string) ($profile['sub'] ?? ''),
            email: (string) ($profile['email'] ?? ''),
            name: (string) ($profile['name'] ?? ''),
            nip: isset($profile['preferred_username']) ? (string) $profile['preferred_username'] : null,
            rawAttributes: $profile
        );
    }
}
