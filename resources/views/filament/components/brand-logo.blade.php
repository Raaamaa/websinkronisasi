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
            <rect x="1.5" y="1.5" width="33" height="33" rx="3" fill="var(--color-paper-hi, #FAFCFC)" stroke="var(--color-ink, #141926)" stroke-width="1.5"/>
            <path d="M11 8.5h9.5L25 13v14.5H11V8.5z" stroke="var(--color-ink, #141926)" stroke-width="1.75" stroke-linejoin="round"/>
            <path d="M20.5 8.5V13H25" stroke="var(--color-ink, #141926)" stroke-width="1.75" stroke-linejoin="round"/>
            <rect x="14" y="17.5" width="8" height="3" rx="1" fill="var(--color-accent, #E07A2F)"/>
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
