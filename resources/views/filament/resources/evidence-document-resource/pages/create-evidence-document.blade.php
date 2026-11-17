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

        {{-- Tata Letak 2 Kolom --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-7 xl:col-span-8">
                <div class="studio-form-wrap">
                    <x-filament-panels::form wire:submit="create">
                        {{ $this->form }}

                        <div class="studio-form-actions">
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
                <div class="vintage-card studio-side-card">
                    <h3 class="studio-card-title">
                        Alur Pemeriksaan Berkas
                    </h3>

                    <div class="studio-steps">
                        <div>
                            <div class="font-bold">1. Cek Format &amp; Keamanan Berkas</div>
                            <p class="studio-card-note">Sistem memastikan berkas asli berupa PDF, Word, Excel, atau gambar (maksimal 20 MB).</p>
                        </div>
                        <div>
                            <div class="font-bold">2. Cek Berkas Kembar</div>
                            <p class="studio-card-note">Berkas yang persis sama tidak akan disimpan dua kali. <a href="{{ url('/admin/cari-dokumen') }}" class="vintage-link">Cek dulu di Cari Dokumen</a></p>
                        </div>
                        <div>
                            <div class="font-bold">3. Verifikasi oleh Admin</div>
                            <p class="studio-card-note">Admin memeriksa dokumen Anda dan mengesahkan Lembar Kerja tujuannya.</p>
                        </div>
                    </div>
                </div>

                <div class="vintage-card studio-side-card">
                    <div class="studio-side-head">
                        <h3 class="studio-card-title">
                            Kiriman Terakhir Anda
                        </h3>
                        <a href="{{ $this->getResource()::getUrl('index') }}" class="vintage-link">
                            Lihat Semua
                        </a>
                    </div>

                    <div class="studio-recent-list">
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
                            <div class="studio-recent-item">
                                <div class="studio-recent-top">
                                    <p class="studio-recent-name" title="{{ $recent->document_name }}">
                                        {{ $recent->document_name }}
                                    </p>
                                    <span class="{{ $badgeClass }}">{{ $statusText }}</span>
                                </div>
                                <p class="studio-card-note">
                                    {{ $recent->created_at?->diffForHumans() ?? 'Baru saja' }}, {{ (int) ($recent->official_lks_count ?? 0) }} LK resmi
                                </p>
                            </div>
                        @empty
                            <p class="text-sm vintage-muted text-center py-4">
                                Belum ada berkas yang dikirim.
                            </p>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
