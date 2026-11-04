<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $users = $this->getUsers();
    @endphp

    <div class="space-y-6">
        {{-- Breadcrumb & Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-medium">
                    <a href="/admin/monitor-matrix" class="flex items-center gap-1 hover:text-sky-600 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Beranda</span>
                    </a>
                    <span>/</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">Pengguna</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
                    Manajemen Pengguna
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola akun pengguna, penugasan peran (RBAC), dan status akses instansi.
                </p>
            </div>

            <div>
                @if(auth()->user()?->isSuperAdmin())
                    <button
                        type="button"
                        wire:click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs sm:text-sm font-bold shadow-2xs transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        <span>Tambah Pengguna Baru</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 3 Summary KPI Cards Formal --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Total Pengguna --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pengguna</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">{{ $stats['total'] }}</span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pegawai</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                        <span>{{ $stats['sso_count'] }} login via SSO Keycloak</span>
                    </div>
                </div>
            </div>

            {{-- Pengelola Sistem --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pengelola Sistem</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">{{ $stats['manager_count'] }}</span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akun</span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        <span>{{ $stats['super_admin_count'] }} Super Admin, {{ $stats['admin_count'] }} Admin</span>
                    </div>
                </div>
            </div>

            {{-- Akun Nonaktif --}}
            <div class="gov-metric-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Akun Nonaktif</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold font-mono text-slate-900 dark:text-slate-100 tracking-tight">{{ $stats['inactive_count'] }}</span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akun</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                        <span>Akses dinonaktifkan</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter & Search Bar Formal (Dual-Theme) --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors">
            {{-- Search Box --}}
            <div class="relative w-full lg:w-80">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari berdasarkan Nama, NIP, atau email..."
                    class="block w-full pl-9 pr-3 py-1.5 text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-sky-600 transition"
                />
            </div>

            {{-- Role Filter Tabs --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
                <button
                    type="button"
                    wire:click="setRoleFilter('all')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $roleFilter === 'all' ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                >
                    Semua ({{ $stats['total'] }})
                </button>
                <button
                    type="button"
                    wire:click="setRoleFilter('super_admin')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $roleFilter === 'super_admin' ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                >
                    Super Admin ({{ $stats['super_admin_count'] }})
                </button>
                <button
                    type="button"
                    wire:click="setRoleFilter('admin')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $roleFilter === 'admin' ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                >
                    Admin ({{ $stats['admin_count'] }})
                </button>
                <button
                    type="button"
                    wire:click="setRoleFilter('pegawai')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $roleFilter === 'pegawai' ? 'bg-sky-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                >
                    Pegawai ({{ $stats['pegawai_count'] }})
                </button>
            </div>

            {{-- Login Method Dropdown --}}
            <div class="w-full lg:w-56">
                <select
                    wire:model.live="authProviderFilter"
                    class="block w-full py-1.5 pl-3 pr-8 text-xs sm:text-sm font-semibold rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-sky-500 focus:border-sky-500 transition"
                >
                    <option value="all">Metode Login: Semua</option>
                    <option value="sso">SSO Keycloak BPS</option>
                    <option value="local">Akun Lokal</option>
                </select>
            </div>
        </div>

        {{-- Users Table Card --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xs overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4 sm:px-6">Pengguna</th>
                            <th class="py-3.5 px-4">NIP</th>
                            <th class="py-3.5 px-4">Peran / Role</th>
                            <th class="py-3.5 px-4">Metode Login</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Login Terakhir</th>
                            <th class="py-3.5 px-4 sm:pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-xs sm:text-sm bg-white dark:bg-slate-800">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-750 transition group">
                                {{-- Pengguna (Avatar, Name, Email) --}}
                                <td class="py-3.5 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 font-extrabold text-xs sm:text-sm flex items-center justify-center flex-shrink-0 border border-sky-200 dark:border-sky-800">
                                            {{ $this->getInitials($user->name) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 transition">
                                                {{ $user->name }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- NIP --}}
                                <td class="py-3.5 px-4 font-mono text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap">
                                    {{ $user->nip ?: '-' }}
                                </td>

                                {{-- Role --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->role === 'super_admin')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-sky-50 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                            Super Admin
                                        </span>
                                    @elseif($user->role === 'admin')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600">
                                            Pegawai
                                        </span>
                                    @endif
                                </td>

                                {{-- Metode Login --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->auth_provider === 'sso')
                                        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-medium">
                                            <svg class="w-4 h-4 text-sky-600 dark:text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                            <div class="leading-tight">
                                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">SSO Keycloak</div>
                                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">BPS</div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300 font-medium">
                                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Akun Lokal</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>Aktif</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            <span>Nonaktif</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Login Terakhir --}}
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap text-xs sm:text-sm">
                                    {{ $this->formatLastLogin($user) }}
                                </td>

                                {{-- Aksi --}}
                                <td class="py-3.5 px-4 sm:pr-6 text-right whitespace-nowrap">
                                    @if($this->isUserProtected($user))
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 dark:text-slate-500">
                                            <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <span>Dilindungi</span>
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="openEditDrawer('{{ $user->id }}')"
                                            class="text-xs sm:text-sm font-bold text-sky-600 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-300 hover:underline transition"
                                        >
                                            Edit Peran
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <span class="font-bold text-slate-700 dark:text-slate-300">Tidak ada data pengguna yang sesuai filter</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table Footer / Pagination --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex flex-wrap items-center gap-3 whitespace-nowrap">
                    <div>
                        Menampilkan <span class="font-bold text-slate-900 dark:text-slate-100">{{ $users->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900 dark:text-slate-100">{{ $users->lastItem() ?? 0 }}</span> dari <span class="font-bold text-slate-900 dark:text-slate-100">{{ $users->total() }}</span> pengguna terdaftar
                    </div>

                    <div class="flex items-center gap-1.5">
                        <label for="users-per-page" class="text-slate-500 dark:text-slate-400 font-medium">Baris:</label>
                        <select
                            id="users-per-page"
                            wire:model.live="perPage"
                            class="py-1 pl-2.5 pr-8 text-xs font-bold rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 min-w-[4.25rem] cursor-pointer focus:outline-none focus:border-sky-600"
                        >
                            <option value="6">6</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                {{-- Windowed Pagination Links (Max 7 elements, zero wrapping collision) --}}
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
                        class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-xs"
                        title="Halaman Sebelumnya"
                        aria-label="Sebelumnya"
                    >
                        &lt;
                    </button>

                    @foreach($window as $item)
                        @if($item === '...')
                            <span class="px-2 py-1 text-slate-400 font-bold text-xs select-none">...</span>
                        @else
                            <button
                                type="button"
                                wire:click="selectPage({{ $item }})"
                                class="px-2.5 py-1.5 rounded-lg border font-bold transition text-xs {{ $currentPage === $item ? 'bg-sky-600 text-white border-sky-600 shadow-2xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700' }}"
                            >
                                {{ $item }}
                            </button>
                        @endif
                    @endforeach

                    <button
                        type="button"
                        wire:click="nextCustomPage({{ $lastPage }})"
                        @disabled($currentPage === $lastPage)
                        class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-xs"
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
            {{-- Backdrop --}}
            <div
                wire:click="closeEditDrawer"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-300"
            ></div>

            {{-- Drawer Container --}}
            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md md:max-w-lg bg-white dark:bg-slate-800 shadow-2xl flex flex-col justify-between overflow-y-auto border-l border-slate-200 dark:border-slate-700 transition-colors">
                    {{-- Drawer Content --}}
                    <div class="p-6 space-y-6">
                        {{-- Top Header --}}
                        <div class="flex items-start justify-between pb-4 border-b border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 font-extrabold text-sm flex items-center justify-center flex-shrink-0 border border-sky-200 dark:border-sky-800">
                                    {{ $this->getInitials($editingUserName) }}
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                        Ubah Hak Akses
                                    </div>
                                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-slate-100 leading-tight">
                                        {{ $editingUserName }}
                                    </h2>
                                    <div class="text-xs font-mono text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $editingUserNip }}
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                wire:click="closeEditDrawer"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Satker Card --}}
                        <div class="bg-sky-50/70 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-800 rounded-xl p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/60 text-sky-700 dark:text-sky-300 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Unit Kerja Satker</div>
                                    <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">{{ $satkerName }}</div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                {{ $satkerType }}
                            </span>
                        </div>

                        {{-- Section: Role Options --}}
                        <div class="space-y-3">
                            <div class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                Pilih Tingkat Peran (RBAC)
                            </div>

                            {{-- Super Admin Option --}}
                            <div
                                wire:click="{{ auth()->user()?->isSuperAdmin() ? "\$set('editingRole', 'super_admin')" : '' }}"
                                class="p-4 rounded-xl border transition-all cursor-pointer {{ $editingRole === 'super_admin' ? 'border-sky-500 bg-sky-50/40 dark:bg-sky-950/40 ring-2 ring-sky-300 dark:ring-sky-700' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600' }} {{ !auth()->user()?->isSuperAdmin() ? 'opacity-60 cursor-not-allowed' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model="editingRole"
                                        value="super_admin"
                                        @disabled(!auth()->user()?->isSuperAdmin())
                                        class="mt-1 text-sky-600 focus:ring-sky-500"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">Super Admin</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                                OTORITAS PENUH
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                            Kelola seluruh pengguna, ubah master lembar kerja, audit log & konfigurasi SIKN.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Admin Option --}}
                            <div
                                wire:click="{{ auth()->user()?->isSuperAdmin() ? "\$set('editingRole', 'admin')" : '' }}"
                                class="p-4 rounded-xl border transition-all cursor-pointer {{ $editingRole === 'admin' ? 'border-sky-500 bg-sky-50/40 dark:bg-sky-950/40 ring-2 ring-sky-300 dark:ring-sky-700' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600' }} {{ !auth()->user()?->isSuperAdmin() ? 'opacity-60 cursor-not-allowed' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model="editingRole"
                                        value="admin"
                                        @disabled(!auth()->user()?->isSuperAdmin())
                                        class="mt-1 text-sky-600 focus:ring-sky-500"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">Admin</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                                VERIFIKATOR
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                            Kelola dokumen bukti dukung, eksekusi rekonsiliasi data, dan verifikasi berkas.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Pegawai Option --}}
                            <div
                                wire:click="{{ auth()->user()?->isSuperAdmin() ? "\$set('editingRole', 'pegawai')" : '' }}"
                                class="p-4 rounded-xl border transition-all cursor-pointer {{ $editingRole === 'pegawai' ? 'border-sky-500 bg-sky-50/40 dark:bg-sky-950/40 ring-2 ring-sky-300 dark:ring-sky-700' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-600' }} {{ !auth()->user()?->isSuperAdmin() ? 'opacity-60 cursor-not-allowed' : '' }}"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model="editingRole"
                                        value="pegawai"
                                        @disabled(!auth()->user()?->isSuperAdmin())
                                        class="mt-1 text-sky-600 focus:ring-sky-500"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">Pegawai</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600">
                                                OPERATOR
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                            Akses matriks monitor publik dan unggah berkas bukti dukung unit kerja sendiri.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if(!auth()->user()?->isSuperAdmin())
                                <p class="text-xs text-amber-600 dark:text-amber-400 italic">
                                    * Hanya Super Admin yang berwenang mengubah peran (role) pengguna.
                                </p>
                            @endif
                        </div>

                        {{-- Section: Account Status Toggle --}}
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                Status Akun Pengguna
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    Izinkan akun masuk ke portal Websinkronisasi
                                </span>
                                <button
                                    type="button"
                                    wire:click="$toggle('editingIsActive')"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden {{ $editingIsActive ? 'bg-sky-600' : 'bg-slate-300 dark:bg-slate-700' }}"
                                >
                                    <span class="sr-only">Toggle Akun</span>
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out {{ $editingIsActive ? 'translate-x-5' : 'translate-x-0' }}"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        {{-- Info Alert --}}
                        <div class="bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-sky-900 dark:text-sky-200">
                            <svg class="w-4 h-4 text-sky-600 dark:text-sky-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="leading-relaxed">
                                Setiap pergantian hak akses akan dicatat dalam <span class="font-bold">Jejak Audit</span> untuk keperluan tata kelola data BPS RI.
                            </p>
                        </div>

                        {{-- Error callout if any --}}
                        @if($drawerError)
                            <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs text-rose-700 dark:text-rose-300">
                                {{ $drawerError }}
                            </div>
                        @endif
                    </div>

                    {{-- Drawer Footer --}}
                    <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeEditDrawer"
                            class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="saveUserAccess"
                            class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 active:scale-95 rounded-lg shadow-2xs transition flex items-center gap-1.5"
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
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                {{-- Backdrop --}}
                <div
                    wire:click="closeCreateModal"
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-300"
                ></div>

                {{-- Modal Card --}}
                <div class="relative bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700 transform transition-all sm:max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-start justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-slate-100">
                                Tambah Pengguna Baru
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Daftarkan akun pengguna ke dalam portal Websinkronisasi.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if($createError)
                        <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs p-3 rounded-lg">
                            {{ $createError }}
                        </div>
                    @endif

                    <div class="space-y-3.5 text-xs sm:text-sm">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">Nama Lengkap &amp; Gelar</label>
                            <input
                                type="text"
                                wire:model="newName"
                                placeholder="Contoh: Akhmad Rizki, S.Tr.Stat."
                                class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-sky-500 focus:border-sky-500"
                            />
                            @error('newName') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">Alamat Email (@bps.go.id)</label>
                            <input
                                type="email"
                                wire:model="newEmail"
                                placeholder="nama.pengguna@bps.go.id"
                                class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-sky-500 focus:border-sky-500"
                            />
                            @error('newEmail') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">NIP (18 Digit)</label>
                                <input
                                    type="text"
                                    wire:model="newNip"
                                    placeholder="199304122015021001"
                                    class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-sky-500 focus:border-sky-500 font-mono"
                                />
                                @error('newNip') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">Tingkat Peran (RBAC)</label>
                                <select
                                    wire:model="newRole"
                                    class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-sky-500 focus:border-sky-500 font-semibold"
                                >
                                    <option value="pegawai">Pegawai (Operator)</option>
                                    <option value="admin">Admin (Verifikator)</option>
                                    <option value="super_admin">Super Admin (Otoritas Penuh)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">Metode Login</label>
                                <select
                                    wire:model.live="newAuthProvider"
                                    class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-sky-500 focus:border-sky-500 font-semibold"
                                >
                                    <option value="sso">SSO Keycloak BPS</option>
                                    <option value="local">Akun Lokal Manual</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">Unit Kerja Satker</label>
                                <input
                                    type="text"
                                    wire:model="newSatker"
                                    class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-sky-500 focus:border-sky-500"
                                />
                            </div>
                        </div>

                        @if($newAuthProvider === 'local')
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">Kata Sandi Awal</label>
                                <input
                                    type="password"
                                    wire:model="newPassword"
                                    placeholder="Minimal 8 karakter"
                                    class="w-full text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-sky-500 focus:border-sky-500"
                                />
                                @error('newPassword') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-lg transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            wire:click="createUser"
                            class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-2xs transition"
                        >
                            Simpan Pengguna
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
