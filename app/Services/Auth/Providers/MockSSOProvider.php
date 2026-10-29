<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use App\Services\Auth\Contracts\SSOProviderInterface;
use App\Services\Auth\DTOs\SSOUserData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MockSSOProvider implements SSOProviderInterface
{
    /**
     * Redirect to mock SSO selection form or direct callback.
     */
    public function getRedirectResponse(): Response
    {
        return new RedirectResponse('/auth/sso/mock-login');
    }

    /**
     * Handle mock callback with simulated employee data.
     */
    public function handleCallback(Request $request): SSOUserData
    {
        $mockType = (string) $request->input('mock_user', 'pegawai_sample');

        if ($mockType === 'admin_sample') {
            return new SSOUserData(
                id: 'mock-sso-admin-002',
                email: 'admin.sample@bps.go.id',
                name: 'Admin BPS Contoh',
                nip: '198801012010011002',
                rawAttributes: [
                    'preferred_username' => '198801012010011002',
                    'email' => 'admin.sample@bps.go.id',
                    'name' => 'Admin BPS Contoh',
                ]
            );
        }

        if ($mockType === 'inactive_sample') {
            return new SSOUserData(
                id: 'mock-sso-inactive-003',
                email: 'pegawai.inactive@bps.go.id',
                name: 'Pegawai Nonaktif',
                nip: '199201012018011003',
                rawAttributes: [
                    'preferred_username' => '199201012018011003',
                    'email' => 'pegawai.inactive@bps.go.id',
                    'name' => 'Pegawai Nonaktif',
                ]
            );
        }

        // Default: Pegawai
        return new SSOUserData(
            id: 'mock-sso-pegawai-001',
            email: 'pegawai.sample@bps.go.id',
            name: 'Pegawai BPS Contoh',
            nip: '199001012015011001',
            rawAttributes: [
                'preferred_username' => '199001012015011001',
                'email' => 'pegawai.sample@bps.go.id',
                'name' => 'Pegawai BPS Contoh',
            ]
        );
    }
}
