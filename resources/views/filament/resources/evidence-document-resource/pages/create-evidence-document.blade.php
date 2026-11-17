<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 studio-header-band">
            <div>
                <nav class="flex items-center gap-2 text-sm vintage-muted mb-2">
                    <a href="/admin/cari-dokumen" class="vintage-link">Beranda</a>
                    <span>/</span>
                    <a href="{{ $this->getResource()::getUrl('index') }}" class="vintage-link">Unggah Bukti</a>
                    <span>/</span>
                    <span class="font-semibold" style="color: var(--text-main);">Kirim Berkas Baru</span>
                </nav>

                <div class="flex flex-wrap items-center gap-2.5 mb-1">
                    <h1 class="text-2xl sm:text-3xl font-bold vintage-serif-heading">
                        Unggah Dokumen Bukti Dukung
                    </h1>
                    <span class="vintage-badge-disetujui">
                        Tahun {{ date('Y') }}
                    </span>
                </div>
                <p class="text-base vintage-muted max-w-3xl">
                    Kirim berkas PDF, Word, Excel, foto pindaian (maksimal 20 MB), atau tempel tautan Google Drive untuk berkas berukuran besar.
                </p>
            </div>
        </div>

        {{-- Berkas Sampel Siap Uji --}}
        <div class="p-5 vintage-section space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold vintage-serif-heading">
                        Berkas Contoh Resmi (Bila Ingin Uji Coba)
                    </h2>
                    <p class="text-sm vintage-muted">
                        Unduh salah satu berkas contoh di bawah ini untuk mencoba fitur unggah:
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="/sample-documents/SK_Tim_Pembangunan_Zona_Integritas_2026.pdf" download class="vintage-card p-3 flex items-center justify-between gap-2 hover:underline">
                    <div class="min-w-0">
                        <div class="text-sm font-bold truncate" style="color: var(--text-main);">SK Tim ZI 2026</div>
                        <div class="text-xs vintage-muted truncate">PDF • Target: LK ZI</div>
                    </div>
                </a>

                <a href="/sample-documents/Standar_Pelayanan_Publik_BPS_2026.pdf" download class="vintage-card p-3 flex items-center justify-between gap-2 hover:underline">
                    <div class="min-w-0">
                        <div class="text-sm font-bold truncate" style="color: var(--text-main);">Standar Pelayanan PST</div>
                        <div class="text-xs vintage-muted truncate">PDF • Target: LK PEKPPP</div>
                    </div>
                </a>

                <a href="/sample-documents/Laporan_Kinerja_Instansi_Pemerintah_LKjIP_2026.pdf" download class="vintage-card p-3 flex items-center justify-between gap-2 hover:underline">
                    <div class="min-w-0">
                        <div class="text-sm font-bold truncate" style="color: var(--text-main);">Laporan LKjIP 2026</div>
                        <div class="text-xs vintage-muted truncate">PDF • Target: LK SAKIP</div>
                    </div>
                </a>

                <a href="/sample-documents/Daftar_Informasi_Publik_DIP_PPID_2026.docx" download class="vintage-card p-3 flex items-center justify-between gap-2 hover:underline">
                    <div class="min-w-0">
                        <div class="text-sm font-bold truncate" style="color: var(--text-main);">Daftar Informasi DIP</div>
                        <div class="text-xs vintage-muted truncate">DOCX • Target: LK KIP</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Tata Letak 2 Kolom --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-7 xl:col-span-8">
                <div class="vintage-card p-6">
                    <x-filament-panels::form wire:submit="create">
                        {{ $this->form }}

                        <div class="flex flex-wrap items-center gap-3 pt-6 mt-6" style="border-top: 1px solid var(--border-warm);">
                            <button
                                type="submit"
                                class="min-h-[56px] vintage-btn-primary">
                                <span>Kirim Pengajuan Bukti</span>
                            </button>

                            <a
                                href="{{ $this->getResource()::getUrl('index') }}"
                                class="min-h-[56px] vintage-btn-secondary">
                                Batal
                            </a>
                        </div>
                    </x-filament-panels::form>
                </div>
            </div>

            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                <div class="vintage-card p-5 space-y-3">
                    <h3 class="text-base font-bold vintage-serif-heading pb-2" style="border-bottom: 1px solid var(--border-warm);">
                        Alur Pemeriksaan Berkas
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="font-bold">1. Cek Format &amp; Keamanan Berkas</div>
                            <p class="vintage-muted text-xs mt-0.5">Sistem memastikan berkas asli berupa PDF, Word, Excel, atau gambar (maksimal 20 MB).</p>
                        </div>
                        <div>
                            <div class="font-bold">2. Cek Berkas Kembar</div>
                            <p class="vintage-muted text-xs mt-0.5">Berkas yang persis sama tidak akan disimpan dua kali.</p>
                        </div>
                        <div>
                            <div class="font-bold">3. Verifikasi oleh Admin</div>
                            <p class="vintage-muted text-xs mt-0.5">Admin memeriksa dokumen Anda dan mengesahkan Lembar Kerja tujuannya.</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 studio-status-box text-sm space-y-2" style="background-color: var(--status-pending-bg); border: var(--rule-hair) solid var(--status-pending-border); color: var(--status-pending-text);">
                    <h4 class="font-bold text-base">
                        Cegah Dokumen Ganda
                    </h4>
                    <p class="leading-relaxed">
                        Jika berkas sudah pernah dikirim sebelumnya, periksa dulu di halaman Cari Dokumen sebelum mengunggah ulang.
                    </p>
                    <a href="{{ url('/admin/cari-dokumen') }}" class="vintage-link inline-block">
                        Lihat daftar dokumen di Cari Dokumen &rarr;
                    </a>
                </div>

                <div class="vintage-card p-5 space-y-3">
                    <div class="flex items-center justify-between pb-2" style="border-bottom: 1px solid var(--border-warm);">
                        <h3 class="text-base font-bold vintage-serif-heading">
                            Kiriman Terakhir Anda
                        </h3>
                        <a href="{{ $this->getResource()::getUrl('index') }}" class="vintage-link text-sm">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($this->getRecentUploads() as $recent)
                            @php
                                $badgeClass = match ($recent->approval_status) {
                                    'disetujui' => 'vintage-badge-disetujui',
                                    'revisi' => 'vintage-badge-revisi',
                                    'ditolak' => 'vintage-badge-ditolak',
                                    default => 'vintage-badge-menunggu',
                                };
                                $statusText = match ($recent->approval_status) {
                                    'disetujui' => 'Disetujui',
                                    'revisi' => 'Perlu Revisi',
                                    'ditolak' => 'Ditolak',
                                    default => 'Menunggu',
                                };
                            @endphp
                            <div class="flex items-center justify-between p-3 vintage-section gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold truncate" title="{{ $recent->document_name }}">
                                        {{ $recent->document_name }}
                                    </p>
                                    <p class="text-xs vintage-muted">
                                        {{ $recent->created_at?->diffForHumans() ?? 'Baru saja' }} • {{ (int) ($recent->official_lks_count ?? 0) }} LK resmi
                                    </p>
                                </div>
                                <span class="{{ $badgeClass }} flex-shrink-0">
                                    {{ $statusText }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm vintage-muted text-center py-4">
                                Belum ada berkas yang dikirim.
                            </p>
                        @endforelse
                    </div>

                    <div class="pt-3 text-center text-xs vintage-muted" style="border-top: 1px solid var(--border-warm);">
                        Perpustakaan Bukti Dukung BPS Kabupaten Tegal
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
