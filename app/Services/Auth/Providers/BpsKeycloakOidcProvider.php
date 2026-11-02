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

    private string $apiPegawaiUrl;

    public function __construct(?Client $client = null)
    {
        $timeout = (float) config('services.sso.timeout', 10.0);
        $this->httpClient = $client ?? new Client(['timeout' => $timeout]);
        $this->clientId = (string) config('services.sso.client_id', '');
        $this->clientSecret = (string) config('services.sso.client_secret', '');
        $this->redirectUri = (string) config('services.sso.redirect_uri', '');

        $baseUrl = rtrim((string) config('services.sso.base_url', 'https://sso.bps.go.id'), '/');
        $realm = (string) config('services.sso.realm', 'pegawai-bps');

        $this->authUrl = $baseUrl."/auth/realms/{$realm}/protocol/openid-connect/auth";
        $this->tokenUrl = $baseUrl."/auth/realms/{$realm}/protocol/openid-connect/token";
        $this->userinfoUrl = $baseUrl."/auth/realms/{$realm}/protocol/openid-connect/userinfo";
        $this->apiPegawaiUrl = $baseUrl."/auth/realms/{$realm}/api-pegawai/username/";
    }

    public function getAuthUrl(): string
    {
        return $this->authUrl;
    }

    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function getUserinfoUrl(): string
    {
        return $this->userinfoUrl;
    }

    public function getApiPegawaiUrl(): string
    {
        return $this->apiPegawaiUrl;
    }

    /**
     * Redirect user to BPS Keycloak Authorization Page.
     */
    public function getRedirectResponse(): Response
    {
        if ($this->clientId === '') {
            throw new RuntimeException('Client ID SSO BPS belum dikonfigurasi. Pastikan BPS_SSO_CLIENT_ID sudah diisi di file .env.');
        }

        $state = bin2hex(random_bytes(16));
        session(['sso_state' => $state]);

        $scope = (string) config('services.sso.scope', 'openid profile email');

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
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

        if ($request->has('error')) {
            $errorDesc = (string) $request->input('error_description', $request->input('error'));
            throw new RuntimeException('Otorisasi SSO BPS dibatalkan atau gagal: '.$errorDesc);
        }

        if ($code === '') {
            throw new RuntimeException('Kode otorisasi dari SSO BPS tidak ditemukan.');
        }

        if ($expectedState === '' || ! hash_equals($expectedState, $state)) {
            throw new RuntimeException('State CSRF SSO BPS tidak cocok.');
        }

        session()->forget('sso_state');

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
        if (! is_array($profile)) {
            $profile = [];
        }

        $username = $profile['preferred_username'] ?? null;
        $nip = is_string($username) && $username !== '' ? $username : null;
        $name = (string) ($profile['name'] ?? '');
        $email = (string) ($profile['email'] ?? '');

        // 3. Integrasi API Pegawai BPS (Pola GUCI) untuk pengayaan NIP & Nama
        if ($username !== null && $username !== '') {
            try {
                $apiResponse = $this->httpClient->get($this->apiPegawaiUrl.(string) $username, [
                    'headers' => [
                        'Authorization' => 'Bearer '.$accessToken,
                        'Accept' => 'application/json',
                    ],
                ]);

                if ($apiResponse->getStatusCode() === 200) {
                    $pegawaiData = json_decode((string) $apiResponse->getBody(), true);
                    if (is_array($pegawaiData) && count($pegawaiData) > 0) {
                        /** @var array<string, mixed> $pegawai */
                        $pegawai = $pegawaiData[0];
                        if (isset($pegawai['nama']) && is_string($pegawai['nama']) && $pegawai['nama'] !== '') {
                            $name = $pegawai['nama'];
                        } elseif (isset($pegawai['name']) && is_string($pegawai['name']) && $pegawai['name'] !== '') {
                            $name = $pegawai['name'];
                        }
                        if (isset($pegawai['nip']) && is_string($pegawai['nip']) && $pegawai['nip'] !== '') {
                            $nip = $pegawai['nip'];
                        } elseif (isset($pegawai['nipBaru']) && is_string($pegawai['nipBaru']) && $pegawai['nipBaru'] !== '') {
                            $nip = $pegawai['nipBaru'];
                        }
                        if ($email === '') {
                            if (isset($pegawai['email']) && is_string($pegawai['email']) && $pegawai['email'] !== '') {
                                $email = $pegawai['email'];
                            } elseif (isset($pegawai['emailBps']) && is_string($pegawai['emailBps']) && $pegawai['emailBps'] !== '') {
                                $email = $pegawai['emailBps'];
                            }
                        }
                        $profile['pegawai_data'] = $pegawai;
                    }
                }
            } catch (\Throwable) {
                // Graceful degradation: jika API pegawai gagal/timeout, lanjutkan dengan data dasar userinfo
            }
        }

        if ($email === '' && $nip !== null) {
            $email = $nip.'@bps.go.id';
        }

        if ($email === '') {
            throw new RuntimeException('Alamat email tidak ditemukan pada profil SSO BPS Anda. Silakan hubungi administrator IT BPS.');
        }

        return new SSOUserData(
            id: (string) ($profile['sub'] ?? ($nip ?? $email)),
            email: $email,
            name: $name !== '' ? $name : 'Pegawai BPS',
            nip: $nip,
            rawAttributes: $profile
        );
    }
}
