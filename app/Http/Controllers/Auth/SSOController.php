<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\Contracts\SSOProviderInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SSOController extends Controller
{
    /**
     * Redirect user to SSO Provider login page.
     */
    public function login(SSOProviderInterface $provider): Response
    {
        return $provider->getRedirectResponse();
    }

    /**
     * View for Mock SSO selection in local development.
     */
    public function mockLogin(): Response
    {
        return response(
            '<!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="utf-8">
                <title>Simulasi Login SSO BPS (Mock)</title>
                <style>
                    body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f172a; color: #f8fafc; margin: 0; }
                    .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 360px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                    h2 { margin-top: 0; font-size: 1.25rem; color: #38bdf8; text-align: center; }
                    p { font-size: 0.875rem; color: #94a3b8; text-align: center; margin-bottom: 1.5rem; }
                    .btn { display: block; width: 100%; padding: 0.75rem; margin-bottom: 0.75rem; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.875rem; box-sizing: border-box; }
                    .btn-pegawai { background: #0284c7; color: white; }
                    .btn-admin { background: #059669; color: white; }
                    .btn-inactive { background: #dc2626; color: white; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>Simulasi SSO BPS (Lokal)</h2>
                    <p>Pilih akun pengujian untuk melanjutkan otentikasi:</p>
                    <a class="btn btn-pegawai" href="/auth/sso/callback?mock_user=pegawai_sample">Masuk sebagai Pegawai (Read-Only)</a>
                    <a class="btn btn-admin" href="/auth/sso/callback?mock_user=admin_sample">Masuk sebagai Admin (Operasional)</a>
                    <a class="btn btn-inactive" href="/auth/sso/callback?mock_user=inactive_sample">Masuk sebagai Akun Nonaktif (Uji Tolak)</a>
                </div>
            </body>
            </html>'
        );
    }

    /**
     * Handle incoming SSO Callback.
     */
    public function callback(Request $request, SSOProviderInterface $provider): Response
    {
        try {
            $ssoUser = $provider->handleCallback($request);
        } catch (ConnectException | ConnectionException | RequestException $e) {
            Log::warning('Koneksi SSO BPS gagal / timeout: '.$e->getMessage());

            return redirect()->route('login')->with(
                'error',
                'Gagal terhubung ke server SSO BPS. Jika Anda mengakses dari luar kantor BPS, pastikan aplikasi FortiClient VPN Anda sudah terhubung (Connected) sebelum mencoba kembali.'
            );
        } catch (RuntimeException $e) {
            Log::warning('Kesalahan otorisasi SSO BPS: '.$e->getMessage());

            return redirect()->route('login')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Error tidak terduga pada callback SSO: '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Terjadi kesalahan teknis saat menghubungi SSO BPS. Silakan coba kembali.');
        }

        // Cari user yang sudah ada berdasarkan email atau NIP
        $normalizedEmail = strtolower(trim($ssoUser->email));
        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->orWhere(function ($query) use ($ssoUser): void {
                if ($ssoUser->nip !== null && $ssoUser->nip !== '') {
                    $query->where('nip', $ssoUser->nip);
                }
            })
            ->first();

        // Cek status keaktifan akun (AUTH-07, AC-18)
        if ($user !== null && (! $user->is_active || $user->deleted_at !== null)) {
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'action' => 'auth.sso_login_blocked_inactive',
                'entity_type' => 'users',
                'entity_id' => (string) $user->id,
                'old_values' => null,
                'new_values' => json_encode(['email' => $user->email, 'is_active' => $user->is_active]),
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            abort(403, 'Akun Anda dinonaktifkan, silakan hubungi administrator.');
        }

        // Auto-provisioning jika user belum pernah ada (AUTH-10, AC-21)
        if ($user === null) {
            try {
                $user = User::create([
                    'name' => $ssoUser->name,
                    'email' => $normalizedEmail,
                    'nip' => $ssoUser->nip,
                    'role' => 'pegawai',
                    'is_active' => true,
                    'auth_provider' => 'sso',
                    'auth_provider_id' => $ssoUser->id,
                    'must_change_password' => false,
                    'password_hash' => null,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Tangani potensi race condition jika request paralel telah membuat user secara bersamaan
                /** @var User|null $user */
                $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
                if ($user === null) {
                    throw $e;
                }
            }
        }

        // Catat jejak audit login sukses (AUDIT-01)
        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'action' => 'auth.login_sso',
            'entity_type' => 'users',
            'entity_id' => (string) $user->id,
            'old_values' => null,
            'new_values' => json_encode(['email' => $user->email, 'role' => $user->role]),
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }
}
