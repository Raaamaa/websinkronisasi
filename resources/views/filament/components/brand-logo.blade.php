@php
    $logoPath = null;
    if (file_exists(public_path('images/logo-bps-tegal.png'))) {
        $logoPath = asset('images/logo-bps-tegal.png');
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    }
@endphp

<div class="flex items-center gap-3 py-1">
    @if ($logoPath)
        <img src="{{ $logoPath }}"
             alt="Logo BPS Kabupaten Tegal"
             class="w-9 h-9 object-contain flex-shrink-0">
    @else
        <svg class="w-9 h-9 flex-shrink-0" viewBox="0 0 36 36" fill="none" aria-hidden="true">
            <rect width="36" height="36" rx="10" fill="#B5532F"/>
            <path d="M10 11h16M10 18h12M10 25h16" stroke="#FAF9F5" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
    @endif

    <div class="flex flex-col text-left">
        <span class="text-lg font-bold leading-tight vintage-serif-heading" style="color: var(--text-main);">
            ELKA
        </span>
        <span class="text-xs font-semibold leading-tight" style="color: var(--text-secondary);">
            BPS Kabupaten Tegal
        </span>
    </div>
</div>
