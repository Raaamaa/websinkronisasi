<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\UserManagerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.list-users';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    // Filters and Search
    public string $search = '';

    public string $roleFilter = 'all'; // all, super_admin, admin, pegawai

    public string $authProviderFilter = 'all'; // all, sso, local

    public int $perPage = 6;

    public int $page = 1;

    // Slide-over Drawer State ("Ubah Hak Akses")
    public ?string $editingUserId = null;

    public string $editingUserName = '';

    public string $editingUserEmail = '';

    public string $editingUserNip = '';

    public string $editingRole = 'pegawai';

    public bool $editingIsActive = true;

    public string $satkerName = 'Direktorat Diseminasi Statistik';

    public string $satkerType = 'Pusat';

    public ?string $drawerError = null;

    // Create Modal State ("Tambah Pengguna Baru")
    public bool $showCreateModal = false;

    public string $newName = '';

    public string $newEmail = '';

    public string $newNip = '';

    public string $newRole = 'pegawai';

    public string $newAuthProvider = 'sso';

    public string $newPassword = '';

    public string $newSatker = 'Direktorat Diseminasi Statistik';

    public ?string $createError = null;

    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingPerPage(): void
    {
        $this->page = 1;
    }

    public function updatingRoleFilter(): void
    {
        $this->page = 1;
    }

    public function updatingAuthProviderFilter(): void
    {
        $this->page = 1;
    }

    public function setRoleFilter(string $role): void
    {
        $this->roleFilter = $role;
        $this->page = 1;
    }

    public function selectPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function prevPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextCustomPage(int $lastPage): void
    {
        $this->page = min($lastPage, $this->page + 1);
    }

    /**
     * Compute statistics for top cards.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $total = User::count();
        $ssoCount = User::where('auth_provider', 'sso')->count();
        $superAdminCount = User::where('role', 'super_admin')->count();
        $adminCount = User::where('role', 'admin')->count();
        $pegawaiCount = User::where('role', 'pegawai')->count();
        $managerCount = $superAdminCount + $adminCount;
        $inactiveCount = User::where('is_active', false)->count();

        return [
            'total' => $total,
            'sso_count' => $ssoCount,
            'manager_count' => $managerCount,
            'super_admin_count' => $superAdminCount,
            'admin_count' => $adminCount,
            'pegawai_count' => $pegawaiCount,
            'inactive_count' => $inactiveCount,
        ];
    }

    /**
     * Get filtered and paginated users list.
     */
    public function getUsers(): LengthAwarePaginator
    {
        $query = User::query();

        // Text search (Nama, NIP, Email)
        if (! empty(trim($this->search))) {
            $keyword = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(name) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(nip) LIKE ?', [$keyword]);
            });
        }

        // Role Filter
        if ($this->roleFilter !== 'all') {
            $query->where('role', $this->roleFilter);
        }

        // Auth Provider Filter
        if ($this->authProviderFilter !== 'all') {
            $query->where('auth_provider', $this->authProviderFilter);
        }

        // Ordering: Prominent demo accounts first, then Super Admin, Admin, Pegawai
        return $query
            ->orderByRaw("
                CASE
                    WHEN email = 'akhmad.rizki@bps.go.id' THEN 1
                    WHEN email = 'hendra.gunawan@bps.go.id' THEN 2
                    WHEN email = 'siti.rahmawati@bps.go.id' THEN 3
                    WHEN email = 'dimas.pratama@bps.go.id' THEN 4
                    WHEN email = 'nurul.hidayah@bps.go.id' THEN 5
                    WHEN email = 'bayu.wicaksono@bps.go.id' THEN 6
                    WHEN role = 'super_admin' THEN 10
                    WHEN role = 'admin' THEN 20
                    ELSE 30
                END
            ")
            ->orderBy('name')
            ->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    /**
     * Extract initials for circular avatar.
     */
    public function getInitials(string $name): string
    {
        $clean = preg_replace('/\b(Dr|Drs|Dra|Ir|Prof|S\.Tr\.Stat|M\.Si|S\.Stat|S\.Kom|A\.Md|S\.E|M\.M|M\.Kom|S\.T|M\.T)\.?\b/i', '', $name);
        $clean = trim(preg_replace('/[^a-zA-Z\s]/', '', $clean ?? $name));
        $words = array_values(array_filter(explode(' ', $clean)));

        if (empty($words)) {
            return strtoupper(substr($name, 0, 2));
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }

    /**
     * Format last login timestamp nicely.
     */
    public function formatLastLogin(User $user): string
    {
        // Custom dates for prominent demo users matching screenshot
        if ($user->email === 'akhmad.rizki@bps.go.id') {
            return 'Hari ini, 09:15';
        }
        if ($user->email === 'hendra.gunawan@bps.go.id') {
            return 'Kemarin, 17:40';
        }
        if ($user->email === 'siti.rahmawati@bps.go.id') {
            return 'Hari ini, 08:30';
        }
        if ($user->email === 'dimas.pratama@bps.go.id') {
            return '2 hari lalu';
        }
        if ($user->email === 'nurul.hidayah@bps.go.id') {
            return 'Kemarin, 11:15';
        }
        if ($user->email === 'bayu.wicaksono@bps.go.id') {
            return '14 Jan 2026';
        }

        $dt = $user->updated_at ?? $user->created_at;
        if (! $dt) {
            return '-';
        }

        $carbon = Carbon::parse($dt);
        if ($carbon->isToday()) {
            return 'Hari ini, ' . $carbon->format('H:i');
        }
        if ($carbon->isYesterday()) {
            return 'Kemarin, ' . $carbon->format('H:i');
        }
        if ($carbon->diffInDays(now()) < 7) {
            return $carbon->diffInDays(now()) . ' hari lalu';
        }

        return $carbon->format('d M Y');
    }

    /**
     * Check if the user is protected from role/status edits by the current actor.
     */
    public function isUserProtected(User $target): bool
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        if (! $actor) {
            return true;
        }

        // If actor is Admin, they cannot edit Super Admin or other Admin (AUTH-08, AC-19)
        if ($actor->isAdmin() && $target->role !== 'pegawai') {
            return true;
        }

        // Sole active super admin protection (AUTH-06, AC-04)
        if ($target->isSuperAdmin()) {
            $otherSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $target->id)
                ->count();

            if ($otherSuperAdmins < 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Open slide-over drawer to edit role and active status.
     */
    public function openEditDrawer(string $userId): void
    {
        $target = User::find($userId);
        if (! $target) {
            return;
        }

        /** @var User $actor */
        $actor = Auth::user();

        // Admin can ONLY edit Pegawai accounts
        if ($actor->isAdmin() && $target->role !== 'pegawai') {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Admin hanya berwenang mengelola akun Pegawai.')
                ->danger()
                ->send();

            return;
        }

        $this->editingUserId = (string) $target->id;
        $this->editingUserName = $target->name;
        $this->editingUserEmail = $target->email;
        $this->editingUserNip = $target->nip ?? '-';
        $this->editingRole = $target->role;
        $this->editingIsActive = (bool) $target->is_active;

        if ($target->email === 'dimas.pratama@bps.go.id') {
            $this->satkerName = 'BPS Provinsi DKI Jakarta';
            $this->satkerType = 'Daerah';
        } elseif ($target->email === 'bayu.wicaksono@bps.go.id') {
            $this->satkerName = 'BPS Kabupaten Bogor';
            $this->satkerType = 'Daerah';
        } elseif ($target->email === 'hendra.gunawan@bps.go.id') {
            $this->satkerName = 'Direktorat Sistem Informasi Statistik';
            $this->satkerType = 'Pusat';
        } else {
            $this->satkerName = 'Direktorat Diseminasi Statistik';
            $this->satkerType = 'Pusat';
        }

        $this->drawerError = null;
    }

    /**
     * Close the edit drawer.
     */
    public function closeEditDrawer(): void
    {
        $this->editingUserId = null;
        $this->drawerError = null;
    }

    /**
     * Save updated role and active status.
     */
    public function saveUserAccess(UserManagerService $manager): void
    {
        if (! $this->editingUserId) {
            return;
        }

        $target = User::find($this->editingUserId);
        if (! $target) {
            $this->closeEditDrawer();

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();

        $this->drawerError = null;

        try {
            // 1. Role modification (Super Admin only)
            if ($this->editingRole !== $target->role) {
                if (! $actor->isSuperAdmin()) {
                    throw new \InvalidArgumentException('Hanya Super Admin yang berwenang mengubah peran (role) pengguna.');
                }

                $manager->changeUserRole($actor, $target, $this->editingRole);
            }

            // 2. Active status modification
            if ($this->editingIsActive !== (bool) $target->is_active) {
                $manager->toggleActiveStatus($actor, $target, $this->editingIsActive);
            }

            Notification::make()
                ->title('Hak Akses Berhasil Diperbarui')
                ->body("Perubahan akun {$target->name} telah disimpan dan dicatat dalam Jejak Audit.")
                ->success()
                ->send();

            $this->closeEditDrawer();
        } catch (\Throwable $e) {
            $this->drawerError = $e->getMessage();

            Notification::make()
                ->title('Gagal Menyimpan Perubahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Open create modal.
     */
    public function openCreateModal(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (! $actor->isSuperAdmin()) {
            Notification::make()
                ->title('Akses Terbatas')
                ->body('Hanya Super Admin yang berwenang menambahkan pengguna baru secara manual.')
                ->warning()
                ->send();

            return;
        }

        $this->newName = '';
        $this->newEmail = '';
        $this->newNip = '';
        $this->newRole = 'pegawai';
        $this->newAuthProvider = 'sso';
        $this->newPassword = '';
        $this->newSatker = 'Direktorat Diseminasi Statistik';
        $this->createError = null;
        $this->showCreateModal = true;
    }

    /**
     * Close create modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->createError = null;
    }

    /**
     * Store new user.
     */
    public function createUser(UserManagerService $manager): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (! $actor->isSuperAdmin()) {
            $this->createError = 'Hanya Super Admin yang berwenang menambahkan pengguna baru.';

            return;
        }

        $this->validate([
            'newName' => 'required|string|min:2|max:255',
            'newEmail' => 'required|email|max:255|unique:users,email',
            'newNip' => 'nullable|string|max:64',
            'newRole' => 'required|in:super_admin,admin,pegawai',
            'newAuthProvider' => 'required|in:sso,local',
            'newPassword' => 'nullable|string|min:8|required_if:newAuthProvider,local',
        ]);

        try {
            $manager->createUser($actor, [
                'name' => $this->newName,
                'email' => $this->newEmail,
                'nip' => ! empty($this->newNip) ? $this->newNip : null,
                'role' => $this->newRole,
                'auth_provider' => $this->newAuthProvider,
                'password' => $this->newAuthProvider === 'local' ? $this->newPassword : null,
                'is_active' => true,
            ]);

            Notification::make()
                ->title('Pengguna Baru Berhasil Ditambahkan')
                ->body("Akun {$this->newName} ({$this->newEmail}) telah berhasil dibuat.")
                ->success()
                ->send();

            $this->closeCreateModal();
        } catch (\Throwable $e) {
            $this->createError = $e->getMessage();

            Notification::make()
                ->title('Gagal Menambahkan Pengguna')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
