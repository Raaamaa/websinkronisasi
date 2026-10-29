<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserManagerService
{
    public function __construct(
        private readonly SessionManagerService $sessionManager,
    ) {}

    /**
     * Activate or deactivate a user account with strict server-side RBAC guard rails.
     */
    public function toggleActiveStatus(User $actor, User $target, bool $isActive): void
    {
        // 1. Cegah menonaktifkan akun sendiri (Self-lockout prevention)
        if ($actor->id === $target->id && ! $isActive) {
            throw new InvalidArgumentException('Dilarang menonaktifkan akun sendiri yang sedang aktif digunakan.');
        }

        // 2. Admin hanya berwenang mengubah status akun Pegawai (AUTH-08, D5, AC-19, AC-20)
        if ($actor->isAdmin() && $target->role !== 'pegawai') {
            abort(403, 'Admin hanya memiliki wewenang mengaktifkan atau menonaktifkan akun Pegawai.');
        }

        // 3. Pegawai tidak berwenang mengelola pengguna
        if ($actor->isPegawai()) {
            abort(403, 'Pegawai tidak memiliki wewenang untuk mengelola status akun pengguna.');
        }

        // 4. Proteksi Super Admin Terakhir (AUTH-06, AC-04)
        if ($target->isSuperAdmin() && ! $isActive) {
            $activeSuperAdminsCount = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $target->id)
                ->count();

            if ($activeSuperAdminsCount < 1) {
                abort(422, 'Aksi ditolak: Sistem harus memiliki setidaknya satu Super Admin aktif.');
            }
        }

        $oldStatus = $target->is_active;
        $target->is_active = $isActive;
        $target->save();

        // 5. Catat jejak audit (AUDIT-01)
        DB::table('audit_logs')->insert([
            'user_id' => $actor->id,
            'action' => $isActive ? 'user.activated' : 'user.deactivated',
            'entity_type' => 'users',
            'entity_id' => (string) $target->id,
            'old_values' => json_encode(['is_active' => $oldStatus]),
            'new_values' => json_encode(['is_active' => $isActive]),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        // 6. Jika dinonaktifkan, putus seluruh sesi pengguna seketika (AUTH-07, AC-15)
        if (! $isActive) {
            $this->sessionManager->revokeUserSessions((string) $target->id);
        }
    }

    /**
     * Change a user's role (Super Admin only).
     */
    public function changeUserRole(User $actor, User $target, string $newRole): void
    {
        // 1. Hanya Super Admin yang berwenang mengubah peran pengguna
        if (! $actor->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang berwenang mengubah peran (role) pengguna.');
        }

        if (! in_array($newRole, ['super_admin', 'admin', 'pegawai'], true)) {
            throw new InvalidArgumentException("Peran {$newRole} tidak valid.");
        }

        // 2. Proteksi penurunan role Super Admin terakhir
        if ($target->isSuperAdmin() && $newRole !== 'super_admin') {
            $activeSuperAdminsCount = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $target->id)
                ->count();

            if ($activeSuperAdminsCount < 1) {
                abort(422, 'Aksi ditolak: Sistem harus memiliki setidaknya satu Super Admin aktif.');
            }
        }

        $oldRole = $target->role;
        $target->role = $newRole;
        $target->save();

        // 3. Catat audit log
        DB::table('audit_logs')->insert([
            'user_id' => $actor->id,
            'action' => 'user.role_changed',
            'entity_type' => 'users',
            'entity_id' => (string) $target->id,
            'old_values' => json_encode(['role' => $oldRole]),
            'new_values' => json_encode(['role' => $newRole]),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
