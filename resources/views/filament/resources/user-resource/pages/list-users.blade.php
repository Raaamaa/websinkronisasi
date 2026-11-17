<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $users = $this->getUsers();
    @endphp

    <div class="space-y-6">
        {{-- Breadcrumb & Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 studio-header-band">
            <div>
                <nav class="flex items-center gap-1.5 text-sm vintage-muted mb-1.5 font-medium">
                    <a href="/admin/verification-queue" class="vintage-link flex items-center gap-1">
                        <span>Beranda</span>
                    </a>
                    <span>/</span>
                    <span class="font-semibold" style="color: var(--text-main);">Pengguna</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-bold vintage-serif-heading">
                    Manajemen Pengguna
                </h1>
                <p class="text-base vintage-muted mt-1">
                    Kelola akun pengguna, penugasan peran (RBAC), dan status akses instansi.
                </p>
            </div>

            <div>
                @if(auth()->user()?->isSuperAdmin())
                    <button
                        type="button"
                        wire:click="openCreateModal"
                        class="min-h-[56px] vintage-btn-primary"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        <span>Tambah Pengguna Baru</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 3 Summary KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold vintage-muted">Total Pengguna</span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold num" style="color: var(--text-main);">{{ $stats['total'] }}</span>
                        <span class="text-sm font-semibold vintage-muted">Pegawai</span>
                    </div>
                    <div class="text-sm vintage-muted mt-1">
                        <span>{{ $stats['sso_count'] }} login via SSO Keycloak</span>
                    </div>
                </div>
            </div>

            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold vintage-muted">Pengelola Sistem</span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold num" style="color: var(--text-main);">{{ $stats['manager_count'] }}</span>
                        <span class="text-sm font-semibold vintage-muted">Akun</span>
                    </div>
                    <div class="text-sm vintage-muted mt-1">
                        <span>{{ $stats['super_admin_count'] }} Super Admin, {{ $stats['admin_count'] }} Admin</span>
                    </div>
                </div>
            </div>

            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold vintage-muted">Akun Nonaktif</span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold num" style="color: var(--text-main);">{{ $stats['inactive_count'] }}</span>
                        <span class="text-sm font-semibold vintage-muted">Akun</span>
                    </div>
                    <div class="text-sm vintage-muted mt-1">
                        <span>Akses dinonaktifkan</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Search Bar --}}
        <div class="vintage-card p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="w-full lg:w-80">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari berdasarkan Nama, NIP, atau email..."
                    class="w-full min-h-[56px] px-4 py-3 vintage-input"
                />
            </div>

            <div class="studio-segmented">
                <button
                    type="button"
                    wire:click="setRoleFilter('all')"
                    class="studio-tab px-4 py-2 text-sm font-bold cursor-pointer {{ $roleFilter === 'all' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}"
                >
                    Semua ({{ $stats['total'] }})
                </button>
                <button
                    type="button"
                    wire:click="setRoleFilter('super_admin')"
                    class="studio-tab px-4 py-2 text-sm font-bold cursor-pointer {{ $roleFilter === 'super_admin' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}"
                >
                    Super Admin ({{ $stats['super_admin_count'] }})
                </button>
                <button
                    type="button"
                    wire:click="setRoleFilter('admin')"
                    class="studio-tab px-4 py-2 text-sm font-bold cursor-pointer {{ $roleFilter === 'admin' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}"
                >
                    Admin ({{ $stats['admin_count'] }})
                </button>
                <button
                    type="button"
                    wire:click="setRoleFilter('pegawai')"
                    class="studio-tab px-4 py-2 text-sm font-bold cursor-pointer {{ $roleFilter === 'pegawai' ? 'vintage-btn-primary' : 'vintage-btn-secondary' }}"
                >
                    Pegawai ({{ $stats['pegawai_count'] }})
                </button>
            </div>

            <div class="w-full lg:w-56">
                <select
                    wire:model.live="authProviderFilter"
                    class="w-full min-h-[56px] px-3 py-2.5 vintage-input font-semibold"
                >
                    <option value="all">Metode Login: Semua</option>
                    <option value="sso">SSO Keycloak BPS</option>
                    <option value="local">Akun Lokal</option>
                </select>
            </div>
        </div>

        {{-- Users Table Card --}}
        <div class="vintage-card overflow-hidden studio-fade-in">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="studio-table-head text-sm font-bold">
                            <th class="py-3.5 px-4 sm:px-6">Pengguna</th>
                            <th class="py-3.5 px-4">NIP</th>
                            <th class="py-3.5 px-4">Peran / Role</th>
                            <th class="py-3.5 px-4">Metode Login</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Login Terakhir</th>
                            <th class="py-3.5 px-4 sm:pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-base">
                        @forelse($users as $user)
                            <tr class="studio-table-row">
                                <td class="py-3.5 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="studio-avatar text-sm">
                                            {{ $this->getInitials($user->name) }}
                                        </div>
                                        <div>
                                            <div class="font-bold" style="color: var(--text-main);">
                                                {{ $user->name }}
                                            </div>
                                            <div class="text-sm vintage-muted">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 num text-sm vintage-muted whitespace-nowrap">
                                    {{ $user->nip ?: '-' }}
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->role === 'super_admin')
                                        <span class="vintage-badge-revisi">Super Admin</span>
                                    @elseif($user->role === 'admin')
                                        <span class="vintage-badge-menunggu">Admin</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 text-xs font-bold vintage-section studio-badge-type">Pegawai</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap text-sm" style="color: var(--text-main);">
                                    @if($user->auth_provider === 'sso')
                                        <span class="font-semibold">SSO Keycloak (BPS)</span>
                                    @else
                                        <span class="font-semibold">Akun Lokal</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->is_active)
                                        <span class="vintage-badge-disetujui">Aktif</span>
                                    @else
                                        <span class="vintage-badge-ditolak">Nonaktif</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-sm vintage-muted whitespace-nowrap">
                                    {{ $this->formatLastLogin($user) }}
                                </td>

                                <td class="py-3.5 px-4 sm:pr-6 text-right whitespace-nowrap">
                                    @if($this->isUserProtected($user))
                                        <span class="text-sm font-semibold vintage-muted">Dilindungi</span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="openEditDrawer('{{ $user->id }}')"
                                            class="vintage-btn-secondary"
                                            style="min-height: 44px !important; padding: 0.4rem 0.9rem; font-size: 0.875rem;"
                                        >
                                            Edit Peran
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 studio-empty-state vintage-muted">
                                    <span class="font-bold" style="color: var(--text-main);">Tidak ada data pengguna yang sesuai filter</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table Footer / Pagination --}}
            <div class="studio-table-footer flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 text-sm">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <span class="font-bold" style="color: var(--text-main);">{{ $users->firstItem() ?? 0 }}</span> - <span class="font-bold" style="color: var(--text-main);">{{ $users->lastItem() ?? 0 }}</span> dari <span class="font-bold" style="color: var(--text-main);">{{ $users->total() }}</span> pengguna terdaftar
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="users-per-page" class="vintage-muted font-medium">Baris:</label>
                        <select
                            id="users-per-page"
                            wire:model.live="perPage"
                            class="vintage-input min-w-[4.25rem] px-2.5 py-1 text-sm font-bold cursor-pointer"
                            style="min-height: 44px !important;"
                        >
                            <option value="6">6</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                @php
                    $currentPage = $users->currentPage();
                    $lastPage = max(1, $users->lastPage());
                    $window = [];
                    if ($lastPage <= 7) {
                        $window = range(1, $lastPage);
                    } else {
                        if ($currentPage <= 4) {
                            $window = [1, 2, 3, 4, 5, '...', $lastPage];
                        } elseif ($currentPage >= $lastPage - 3) {
                            $window = [1, '...', $lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
                        } else {
                            $window = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $lastPage];
                        }
                    }
                @endphp

                <div class="flex items-center gap-1.5 self-center md:self-auto flex-wrap">
                    <button
                        type="button"
                        wire:click="prevPage"
                        @disabled($users->onFirstPage())
                        class="studio-page-btn text-sm"
                        title="Halaman Sebelumnya"
                        aria-label="Sebelumnya"
                    >
                        &lt;
                    </button>

                    @foreach($window as $item)
                        @if($item === '...')
                            <span class="px-2 py-1 vintage-muted font-bold text-sm select-none">...</span>
                        @else
                            <button
                                type="button"
                                wire:click="selectPage({{ $item }})"
                                class="studio-page-btn text-sm {{ $currentPage === $item ? 'studio-page-btn-active' : '' }}"
                            >
                                {{ $item }}
                            </button>
                        @endif
                    @endforeach

                    <button
                        type="button"
                        wire:click="nextCustomPage({{ $lastPage }})"
                        @disabled($currentPage === $lastPage)
                        class="studio-page-btn text-sm"
                        title="Halaman Selanjutnya"
                        aria-label="Selanjutnya"
                    >
                        &gt;
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Slide-Over Drawer ("UBAH HAK AKSES") --}}
    @if($editingUserId)
        <div class="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
            <div
                wire:click="closeEditDrawer"
                class="fixed inset-0 studio-modal-backdrop"
            ></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md md:max-w-lg studio-drawer-panel flex flex-col justify-between overflow-y-auto studio-fade-in">
                    <div class="p-6 space-y-6">
                        <div class="flex items-start justify-between pb-4" style="border-bottom: var(--rule-hair) solid var(--border-warm);">
                            <div class="flex items-center gap-3">
                                <div class="studio-avatar studio-avatar-lg">
                                    {{ $this->getInitials($editingUserName) }}
                                </div>
                                <div>
                                    <div class="text-xs font-bold vintage-muted">
                                        Ubah Hak Akses
                                    </div>
                                    <h2 class="text-lg font-bold vintage-serif-heading leading-tight">
                                        {{ $editingUserName }}
                                    </h2>
                                    <div class="text-sm num vintage-muted">
                                        {{ $editingUserNip }}
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                wire:click="closeEditDrawer"
                                class="vintage-btn-secondary"
                                style="min-height: 44px !important; padding: 0.25rem 0.75rem;"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="vintage-section p-4 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-medium vintage-muted">Unit Kerja Satker</div>
                                <div class="text-sm font-bold" style="color: var(--text-main);">{{ $satkerName }}</div>
                            </div>
                            <span class="vintage-badge-menunggu">
                                {{ $satkerType }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div class="text-sm font-bold" style="color: var(--text-main);">
                                Pilih Tingkat Peran (RBAC)
                            </div>

                            <div
                                wire:click="{{ auth()->user()?->isSuperAdmin() ? "\$set('editingRole', 'super_admin')" : '' }}"
                                class="studio-role-option {{ $editingRole === 'super_admin' ? 'studio-role-option-active' : '' }} {{ !auth()->user()?->isSuperAdmin() ? 'opacity-60 cursor-not-allowed' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model="editingRole"
                                        value="super_admin"
                                        @disabled(!auth()->user()?->isSuperAdmin())
                                        class="mt-1"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold" style="color: var(--text-main);">Super Admin</span>
                                            <span class="vintage-badge-revisi">Otoritas Penuh</span>
                                        </div>
                                        <p class="text-sm vintage-muted mt-1 leading-relaxed">
                                            Kelola seluruh pengguna, ubah master lembar kerja, audit log &amp; konfigurasi SIKN.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                wire:click="{{ auth()->user()?->isSuperAdmin() ? "\$set('editingRole', 'admin')" : '' }}"
                                class="studio-role-option {{ $editingRole === 'admin' ? 'studio-role-option-active' : '' }} {{ !auth()->user()?->isSuperAdmin() ? 'opacity-60 cursor-not-allowed' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model="editingRole"
                                        value="admin"
                                        @disabled(!auth()->user()?->isSuperAdmin())
                                        class="mt-1"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold" style="color: var(--text-main);">Admin</span>
                                            <span class="vintage-badge-menunggu">Verifikator</span>
                                        </div>
                                        <p class="text-sm vintage-muted mt-1 leading-relaxed">
                                            Kelola dokumen bukti dukung, eksekusi rekonsiliasi data, dan verifikasi berkas.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                wire:click="{{ auth()->user()?->isSuperAdmin() ? "\$set('editingRole', 'pegawai')" : '' }}"
                                class="studio-role-option {{ $editingRole === 'pegawai' ? 'studio-role-option-active' : '' }} {{ !auth()->user()?->isSuperAdmin() ? 'opacity-60 cursor-not-allowed' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model="editingRole"
                                        value="pegawai"
                                        @disabled(!auth()->user()?->isSuperAdmin())
                                        class="mt-1"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold" style="color: var(--text-main);">Pegawai</span>
                                            <span class="inline-flex px-2 py-0.5 text-xs font-bold vintage-section studio-badge-type">Operator</span>
                                        </div>
                                        <p class="text-sm vintage-muted mt-1 leading-relaxed">
                                            Akses matriks monitor publik dan unggah berkas bukti dukung unit kerja sendiri.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if(!auth()->user()?->isSuperAdmin())
                                <p class="text-sm italic" style="color: var(--status-revision-text);">
                                    * Hanya Super Admin yang berwenang mengubah peran (role) pengguna.
                                </p>
                            @endif
                        </div>

                        <div class="pt-3 space-y-2" style="border-top: var(--rule-hair) solid var(--border-warm);">
                            <div class="text-sm font-bold" style="color: var(--text-main);">
                                Status Akun Pengguna
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm vintage-muted">
                                    Izinkan akun masuk ke portal ELKA
                                </span>
                                <button
                                    type="button"
                                    wire:click="$toggle('editingIsActive')"
                                    class="studio-switch {{ $editingIsActive ? 'studio-switch-active' : '' }}"
                                >
                                    <span class="sr-only">Toggle Akun</span>
                                    <span
                                        class="studio-switch-thumb {{ $editingIsActive ? 'studio-switch-thumb-active' : '' }}"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        <div class="vintage-section p-3.5 text-sm vintage-muted leading-relaxed">
                            Setiap pergantian hak akses akan dicatat dalam <strong style="color: var(--text-main);">Jejak Audit</strong> untuk keperluan tata kelola data BPS RI.
                        </div>

                        @if($drawerError)
                            <div class="studio-status-box p-3 text-sm font-bold" style="background-color: var(--status-rejected-bg); border: var(--rule-hair) solid var(--status-rejected-border); color: var(--status-rejected-text);">
                                {{ $drawerError }}
                            </div>
                        @endif
                    </div>

                    <div class="studio-table-footer p-4 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeEditDrawer"
                            class="vintage-btn-secondary"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveUserAccess"
                            class="vintage-btn-primary"
                        >
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Create Modal ("+ Tambah Pengguna Baru") --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div
                    wire:click="closeCreateModal"
                    class="fixed inset-0 studio-modal-backdrop"
                ></div>

                <div class="relative vintage-card w-full sm:max-w-lg p-6 space-y-4 studio-fade-in">
                    <div class="flex items-start justify-between pb-3" style="border-bottom: var(--rule-hair) solid var(--border-warm);">
                        <div>
                            <h3 class="text-lg font-bold vintage-serif-heading">
                                Tambah Pengguna Baru
                            </h3>
                            <p class="text-sm vintage-muted mt-0.5">
                                Daftarkan akun pengguna ke dalam portal ELKA.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="vintage-btn-secondary"
                            style="min-height: 44px !important; padding: 0.25rem 0.75rem;"
                        >
                            ✕
                        </button>
                    </div>

                    @if($createError)
                        <div class="studio-status-box p-3 text-sm font-bold" style="background-color: var(--status-rejected-bg); border: var(--rule-hair) solid var(--status-rejected-border); color: var(--status-rejected-text);">
                            {{ $createError }}
                        </div>
                    @endif

                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="block font-bold mb-1" style="color: var(--text-main);">Nama Lengkap &amp; Gelar</label>
                            <input
                                type="text"
                                wire:model="newName"
                                placeholder="Contoh: Akhmad Rizki, S.Tr.Stat."
                                class="w-full px-3 py-2.5 vintage-input"
                            />
                            @error('newName') <span class="text-sm font-bold" style="color: var(--status-rejected-text);">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-bold mb-1" style="color: var(--text-main);">Alamat Email (@bps.go.id)</label>
                            <input
                                type="email"
                                wire:model="newEmail"
                                placeholder="nama.pengguna@bps.go.id"
                                class="w-full px-3 py-2.5 vintage-input"
                            />
                            @error('newEmail') <span class="text-sm font-bold" style="color: var(--status-rejected-text);">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1" style="color: var(--text-main);">NIP (18 Digit)</label>
                                <input
                                    type="text"
                                    wire:model="newNip"
                                    placeholder="199304122015021001"
                                    class="w-full px-3 py-2.5 vintage-input num"
                                />
                                @error('newNip') <span class="text-sm font-bold" style="color: var(--status-rejected-text);">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold mb-1" style="color: var(--text-main);">Tingkat Peran (RBAC)</label>
                                <select
                                    wire:model="newRole"
                                    class="w-full px-3 py-2.5 vintage-input font-semibold"
                                >
                                    <option value="pegawai">Pegawai (Operator)</option>
                                    <option value="admin">Admin (Verifikator)</option>
                                    <option value="super_admin">Super Admin (Otoritas Penuh)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold mb-1" style="color: var(--text-main);">Metode Login</label>
                                <select
                                    wire:model.live="newAuthProvider"
                                    class="w-full px-3 py-2.5 vintage-input font-semibold"
                                >
                                    <option value="sso">SSO Keycloak BPS</option>
                                    <option value="local">Akun Lokal Manual</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold mb-1" style="color: var(--text-main);">Unit Kerja Satker</label>
                                <input
                                    type="text"
                                    wire:model="newSatker"
                                    class="w-full px-3 py-2.5 vintage-input"
                                />
                            </div>
                        </div>

                        @if($newAuthProvider === 'local')
                            <div>
                                <label class="block font-bold mb-1" style="color: var(--text-main);">Kata Sandi Awal</label>
                                <input
                                    type="password"
                                    wire:model="newPassword"
                                    placeholder="Minimal 8 karakter"
                                    class="w-full px-3 py-2.5 vintage-input"
                                />
                                @error('newPassword') <span class="text-sm font-bold" style="color: var(--status-rejected-text);">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3" style="border-top: var(--rule-hair) solid var(--border-warm);">
                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="vintage-btn-secondary"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="createUser"
                            class="vintage-btn-primary"
                        >
                            Simpan Pengguna
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
