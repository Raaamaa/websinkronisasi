@php
    $user = auth()->user();
    $roleLabel = match ($user?->role) {
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        default => 'Pegawai',
    };
    $guideText = match ($user?->role) {
        'super_admin' => 'Periksa kiriman dokumen, kelola akun, dan pantau jejak audit.',
        'admin' => 'Cari bukti dukung, kirim berkas baru, atau periksa kiriman pegawai.',
        default => 'Cari dokumen resmi di menu Cari Dokumen atau kirim berkas baru.',
    };
@endphp

<div class="p-3.5 mx-2 my-2 vintage-section text-sm">
    <div class="font-bold mb-1" style="color: var(--text-main);">
        Panduan Singkat ({{ $roleLabel }})
    </div>
    <p class="text-xs leading-relaxed" style="color: var(--text-secondary);">
        {{ $guideText }}
    </p>
    <div class="mt-2 pt-2 text-xs font-semibold" style="border-top: 1px solid var(--border-warm); color: var(--text-secondary);">
        BPS Kabupaten Tegal
    </div>
</div>
