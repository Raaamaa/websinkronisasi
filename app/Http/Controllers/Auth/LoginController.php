<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login modern ala GUCI.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi akun lokal (Super Admin / Admin).
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email atau NIP wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $emailOrNip = trim($validated['email']);
        $throttleKey = Str::transliterate(Str::lower($emailOrNip).'|'.$request->ip());

        // 1. Proteksi Brute-Force Rate Limiting (AUTH-09, RBAC.md 4.3: Maks 5 percobaan per 15 menit)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLog::create([
                'user_id' => null,
                'action' => 'auth.login_rate_limited',
                'entity_type' => 'users',
                'entity_id' => $emailOrNip,
                'old_values' => null,
                'new_values' => ['email_or_nip' => $emailOrNip, 'available_in_seconds' => $seconds],
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam '.$seconds.' detik.');
        }

        /** @var User|null $user */
        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = LOWER(?)', [$emailOrNip])
            ->orWhere('nip', $emailOrNip)
            ->first();

        // 2. Penegakan Aturan Invarian AUTH-11: Pegawai Dilarang Login via Form Lokal
        if ($user !== null && $user->isPegawai()) {
            RateLimiter::hit($throttleKey, 900);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.login_blocked_pegawai_local',
                'entity_type' => 'users',
                'entity_id' => (string) $user->id,
                'old_values' => null,
                'new_values' => ['email' => $user->email, 'role' => $user->role],
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun Pegawai wajib masuk menggunakan tombol Login SSO BPS (Opsi 1).');
        }

        // Akun tanpa kata sandi lokal wajib menggunakan SSO
        if ($user !== null && $user->password_hash === null) {
            RateLimiter::hit($throttleKey, 900);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun ini belum memiliki kata sandi lokal. Silakan masuk menggunakan tombol Login SSO BPS (Opsi 1).');
        }

        // 3. Validasi Password
        if ($user === null || ! Hash::check($validated['password'], (string) $user->password_hash)) {
            RateLimiter::hit($throttleKey, 900);

            AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'auth.login_failed',
                'entity_type' => 'users',
                'entity_id' => $user !== null ? (string) $user->id : 'unregistered',
                'old_values' => null,
                'new_values' => ['email_or_nip' => $emailOrNip],
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Kombinasi email/NIP dan kata sandi tidak cocok.');
        }

        // 4. Cek Status Keaktifan Akun (AUTH-07, AC-18)
        if (! $user->is_active || $user->deleted_at !== null) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.login_blocked_inactive',
                'entity_type' => 'users',
                'entity_id' => (string) $user->id,
                'old_values' => null,
                'new_values' => ['email' => $user->email, 'is_active' => $user->is_active],
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.');
        }

        // 5. Login Sukses: Bersihkan Rate Limiter & Regenerasi Sesi
        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Jejak audit login lokal
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'auth.login_local',
            'entity_type' => 'users',
            'entity_id' => (string) $user->id,
            'old_values' => null,
            'new_values' => ['email' => $user->email, 'role' => $user->role],
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        // Karantina ganti kata sandi untuk seeder Super Admin awal (AUTH-03, AUTH-04, AC-03)
        if ($user->must_change_password) {
            return redirect()->route('auth.password.change');
        }

        return redirect('/admin');
    }

    /**
     * Proses keluar (logout).
     */
    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.logout',
                'entity_type' => 'users',
                'entity_id' => (string) $user->id,
                'old_values' => null,
                'new_values' => ['email' => $user->email],
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
