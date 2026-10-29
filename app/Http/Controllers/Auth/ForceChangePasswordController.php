<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForceChangePasswordController extends Controller
{
    /**
     * Show quarantine force change password form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/admin/login');
        }

        if (! $user->must_change_password) {
            return redirect('/admin');
        }

        return view('auth.force-change-password', [
            'user' => $user,
        ]);
    }

    /**
     * Update password and lift quarantine status.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/admin/login');
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $newPassword = (string) $request->input('new_password');

        if ($user->password_hash && Hash::check($newPassword, $user->password_hash)) {
            return back()->withErrors([
                'new_password' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
            ]);
        }

        $user->password_hash = Hash::make($newPassword);
        $user->must_change_password = false;
        $user->save();

        // Catat jejak audit ganti sandi (AUDIT-01)
        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'action' => 'auth.password_changed',
            'entity_type' => 'users',
            'entity_id' => (string) $user->id,
            'old_values' => json_encode(['must_change_password' => true]),
            'new_values' => json_encode(['must_change_password' => false]),
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect('/admin')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
