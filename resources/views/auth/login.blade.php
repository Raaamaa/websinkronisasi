@php
    $logoPath = null;
    if (file_exists(public_path('images/logo-bps-tegal.png'))) {
        $logoPath = asset('images/logo-bps-tegal.png');
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ELKA BPS Kabupaten Tegal - Perpustakaan Bukti Dukung Kantor.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ELKA - BPS Kabupaten Tegal</title>
    @if ($logoPath)
        <link rel="icon" type="image/png" href="{{ $logoPath }}">
    @endif

    <style>
        /* Hallmark · shape: two-column auth + depth stack · genre: editorial · theme: Field Office + BPS logo palette */
        :root {
            color-scheme: light;
            --brand-blue:   #0093DD;
            --brand-orange: #EB891B;
            --brand-green:  #68B92E;

            --color-paper:    #F6F8FA;
            --color-paper-2:  #EEF2F6;
            --color-paper-3:  #E4EAF1;
            --color-paper-hi: #FFFFFF;
            --color-ink:      #0F1B2D;
            --color-ink-2:    #334155;
            --color-muted:    #526071;
            --color-focus:    #0077B6;
            --color-sage:     #2D600B;
            --color-sage-soft: #EAF5DD;
            --color-cobalt:   #004E7A;
            --color-cobalt-soft: #E6F4FC;
            --color-orange-text: #8A4500;
            --color-orange-soft: #FDF0DE;

            --font-display: ui-serif, Georgia, Cambria, "Times New Roman", serif;
            --font-body: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

            --radius-card:    20px;
            --radius-control: 14px;
            --radius-pill:    999px;
            --rule-hair:      1px;
            --border-soft:    rgba(15,27,45,0.10);
            --border-input:   rgba(15,27,45,0.20);

            --glass-bg:        rgba(255,255,255,0.80);
            --glass-border:    rgba(255,255,255,0.90);
            --glass-subtle-bg: rgba(246,248,250,0.84);

            --btn-primary-from:       #0077B6;
            --btn-primary-to:         #00629A;
            --btn-primary-hover-from: #0086CE;
            --btn-primary-hover-to:   #0077B6;
            --btn-primary-ink:        #FFFFFF;

            --shadow-soft:       0 2px 6px rgba(15,27,45,0.04), 0 12px 28px -12px rgba(15,27,45,0.10);
            --shadow-card-hover: 0 4px 12px rgba(15,27,45,0.07), 0 20px 38px -14px rgba(15,27,45,0.14);
            --ease-out: cubic-bezier(0.16,1,0.3,1);

            /* left panel */
            --panel-brand-from: #002F55;
            --panel-brand-mid:  #004A80;
            --panel-brand-to:   #0070B0;
        }

        html.dark {
            color-scheme: dark;
            --color-paper:    #0E1621;
            --color-paper-2:  #152231;
            --color-paper-3:  #1C2D40;
            --color-paper-hi: #152231;
            --color-ink:      #EAF1F8;
            --color-ink-2:    #C2D0DF;
            --color-muted:    #9DB0C4;
            --color-focus:    #4DB8EE;
            --color-sage:     #A8EB78;
            --color-sage-soft:  #193016;
            --color-cobalt:     #8FD4FF;
            --color-cobalt-soft: #132B42;
            --color-orange-text: #FFC178;
            --color-orange-soft: #332413;
            --border-soft:  rgba(234,241,248,0.12);
            --border-input: rgba(234,241,248,0.22);
            --glass-bg:        rgba(21,34,49,0.84);
            --glass-border:    rgba(234,241,248,0.14);
            --glass-subtle-bg: rgba(28,45,64,0.74);
            --btn-primary-from:       #0077B6;
            --btn-primary-to:         #00629A;
            --btn-primary-hover-from: #0086CE;
            --btn-primary-hover-to:   #006BA6;
            --shadow-soft:       0 2px 8px rgba(0,0,0,0.28), 0 14px 32px -14px rgba(0,0,0,0.50), inset 0 1px 0 rgba(255,255,255,0.05);
            --shadow-card-hover: 0 4px 12px rgba(0,0,0,0.36), 0 20px 40px -14px rgba(0,0,0,0.62), inset 0 1px 0 rgba(255,255,255,0.08);
            --panel-brand-from: #001B33;
            --panel-brand-mid:  #002D52;
            --panel-brand-to:   #004070;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html {
            background-color: var(--color-paper);
            overflow-x: clip;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100dvh;
            font-family: var(--font-body);
            font-size: 17px;
            line-height: 1.5;
            background-color: transparent;
            color: var(--color-ink);
            overflow-x: clip;
            position: relative;
            isolation: isolate;
        }

        /* ambient orbs on the right (auth) side only */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
            background-image:
                radial-gradient(44rem 32rem at 80% 15%, rgba(0,147,221,0.13), transparent 68%),
                radial-gradient(36rem 28rem at 95% 85%, rgba(235,137,27,0.11), transparent 68%),
                radial-gradient(40rem 30rem at 55% 92%, rgba(104,185,46,0.10), transparent 68%);
        }

        :focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        /* ─── Layout ─────────────────────────────────────────────── */
        .login-split-root {
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1fr;
        }

        @media (min-width: 1024px) {
            .login-split-root {
                grid-template-columns: 1.1fr 0.9fr;
            }
        }

        /* ─── Left / Brand panel ─────────────────────────────────── */
        .panel-brand {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 48px 36px;
            /* deep blue gradient */
            background: linear-gradient(155deg,
                var(--panel-brand-from) 0%,
                var(--panel-brand-mid)  52%,
                var(--panel-brand-to)  100%);
        }

        /* decorative orb layer inside left panel */
        .panel-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                radial-gradient(55rem 45rem at 110% -10%, rgba(0,147,221,0.28), transparent 65%),
                radial-gradient(40rem 32rem at -20% 110%, rgba(104,185,46,0.18), transparent 65%),
                radial-gradient(30rem 24rem at 50%  60%,  rgba(235,137,27,0.10), transparent 60%);
        }

        /* subtle grid texture */
        .panel-brand::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* mobile: compact horizontal strip */
        .panel-brand {
            padding: 20px 20px;
            border-bottom: var(--rule-hair) solid rgba(255,255,255,0.12);
        }

        @media (min-width: 1024px) {
            .panel-brand {
                padding: 56px 52px;
                border-bottom: none;
                min-height: 100dvh;
                align-items: flex-start;
                justify-content: center;
            }
        }

        /* content z-index above pseudo layers */
        .brand-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            width: 100%;
            max-width: 460px;
        }

        @media (min-width: 1024px) {
            .brand-inner {
                align-items: flex-start;
            }
        }

        .brand-logo-lockup {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo-img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.30));
        }

        @media (min-width: 1024px) {
            .brand-logo-img {
                width: 72px;
                height: 72px;
            }
        }

        .brand-agency-block {
            display: flex;
            flex-direction: column;
        }

        .brand-agency-name {
            font-weight: 700;
            font-size: 1rem;
            color: rgba(255,255,255,0.95);
            line-height: 1.2;
        }

        .brand-agency-sub {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.3;
        }

        .brand-wordmark-block {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .brand-system-name {
            font-family: var(--font-display);
            font-size: clamp(2.5rem, 4vw + 1rem, 4rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.0;
            margin: 0;
            color: #FFFFFF;
            text-shadow: 0 2px 20px rgba(0,0,0,0.25);
        }

        .brand-tagline {
            font-size: 1.0625rem;
            color: rgba(255,255,255,0.72);
            margin: 0;
            line-height: 1.5;
            max-width: 36ch;
        }

        /* feature list */
        .brand-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255,255,255,0.14);
            border: var(--rule-hair) solid rgba(255,255,255,0.22);
            backdrop-filter: none;
            transition: background 180ms var(--ease-out), transform 180ms var(--ease-out);
        }

        .brand-feature:hover .brand-feature-icon {
            background: rgba(255,255,255,0.22);
            transform: scale(1.08);
        }

        .brand-feature-text {
            display: flex;
            flex-direction: column;
        }

        .brand-feature-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: rgba(255,255,255,0.95);
            line-height: 1.2;
        }

        .brand-feature-desc {
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.58);
            line-height: 1.4;
        }

        /* 3-D doc stack */
        .login-doc-visual {
            display: none;
        }

        @media (min-width: 1024px) {
            .login-doc-visual {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                height: 180px;
            }
        }

        .login-doc-stack {
            position: relative;
            width: 280px;
            height: 170px;
            perspective: 800px;
        }

        .login-doc-sheet {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 130px;
            height: 148px;
            border-radius: 18px;
            background-color: var(--color-paper-hi);
            border: var(--rule-hair) solid rgba(255,255,255,0.40);
            box-shadow: 0 8px 32px rgba(0,0,0,0.28), 0 2px 8px rgba(0,0,0,0.18);
            transition: translate 320ms var(--ease-out), box-shadow 260ms var(--ease-out);
        }

        .login-doc-visual:hover .login-doc-sheet-1 { translate: -24px 6px; }
        .login-doc-visual:hover .login-doc-sheet-2 { translate: 24px 6px; }
        .login-doc-visual:hover .login-doc-sheet-3 { translate: 0 -12px; }

        .login-doc-sheet::before {
            content: "";
            position: absolute;
            left: 18px; right: 18px; top: 24px;
            height: 9px;
            border-radius: 999px;
            background-color: var(--border-input);
        }
        .login-doc-sheet::after {
            content: "";
            position: absolute;
            left: 18px; right: 36px; top: 44px;
            height: 7px;
            border-radius: 999px;
            background-color: var(--border-soft);
        }

        .login-doc-sheet-1 {
            background: linear-gradient(140deg, #E6F4FC, #C5E6F8);
            border-color: rgba(176,218,248,0.60);
            transform: translate(-88%, -50%) rotateX(14deg) rotateY(-16deg) rotateZ(-10deg);
            opacity: 0.90;
            animation: loginFloat1 7s ease-in-out infinite;
        }
        .login-doc-sheet-1::before { background-color: var(--brand-blue); }

        .login-doc-sheet-2 {
            background: linear-gradient(140deg, #FDF0DE, #F9D9A8);
            border-color: rgba(244,200,140,0.60);
            transform: translate(-15%, -48%) rotateX(12deg) rotateY(14deg) rotateZ(9deg);
            opacity: 0.92;
            animation: loginFloat2 7.5s ease-in-out infinite;
        }
        .login-doc-sheet-2::before { background-color: var(--brand-orange); }

        .login-doc-sheet-3 {
            background: linear-gradient(140deg, #EAF5DD, #CCECAD);
            border-color: rgba(176,222,140,0.60);
            transform: translate(-50%, -54%) rotateX(10deg) rotateY(-4deg);
            animation: loginFloat3 6.5s ease-in-out infinite;
        }
        .login-doc-sheet-3::before { background-color: var(--brand-green); }

        @keyframes loginFloat1 {
            0%,100% { transform: translate(-88%, -50%) rotateX(14deg) rotateY(-16deg) rotateZ(-10deg); }
            50%      { transform: translate(-88%, -58%) rotateX(16deg) rotateY(-13deg) rotateZ(-8deg); }
        }
        @keyframes loginFloat2 {
            0%,100% { transform: translate(-15%, -48%) rotateX(12deg) rotateY(14deg) rotateZ(9deg); }
            50%      { transform: translate(-15%, -56%) rotateX(14deg) rotateY(11deg) rotateZ(7deg); }
        }
        @keyframes loginFloat3 {
            0%,100% { transform: translate(-50%, -54%) rotateX(10deg) rotateY(-4deg); }
            50%      { transform: translate(-50%, -63%) rotateX(8deg) rotateY(-2deg); }
        }

        /* pill badge at bottom of brand panel */
        .brand-network-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            background: rgba(255,255,255,0.12);
            border: var(--rule-hair) solid rgba(255,255,255,0.22);
            font-size: 0.8125rem;
            color: rgba(255,255,255,0.72);
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #68B92E;
            box-shadow: 0 0 0 2px rgba(104,185,46,0.30);
            animation: badgePulse 2.4s ease-in-out infinite;
        }

        @keyframes badgePulse {
            0%,100% { opacity: 1; }
            50%      { opacity: 0.55; }
        }

        /* ─── Right / Auth panel ─────────────────────────────────── */
        .panel-auth {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background-color: var(--color-paper);
        }

        @media (min-width: 1024px) {
            .panel-auth {
                padding: 48px 48px;
                min-height: 100dvh;
            }
        }

        .auth-stage-inner {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .auth-heading-block {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .auth-title {
            font-family: var(--font-body);
            font-size: clamp(1.625rem, 1.5vw + 1rem, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0;
            color: var(--color-ink);
            line-height: 1.15;
        }

        .auth-subtitle {
            font-size: 0.9375rem;
            color: var(--color-muted);
            margin: 0;
            line-height: 1.4;
        }

        /* form card */
        .auth-card {
            background-color: var(--glass-bg);
            backdrop-filter: blur(10px) saturate(140%);
            border: var(--rule-hair) solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 28px 24px;
            box-shadow:
                0 2px 6px rgba(15,27,45,0.05),
                0 16px 36px -12px rgba(15,27,45,0.12),
                inset 0 1px 0 rgba(255,255,255,0.80);
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: transform 200ms var(--ease-out), box-shadow 200ms var(--ease-out);
        }

        .auth-card:hover {
            transform: translateY(-2px);
            box-shadow:
                0 4px 12px rgba(15,27,45,0.07),
                0 24px 44px -14px rgba(15,27,45,0.16),
                inset 0 1px 0 rgba(255,255,255,0.90);
        }

        @supports not (backdrop-filter: blur(1px)) {
            .auth-card { background-color: var(--color-paper-hi); }
        }

        /* alerts */
        .auth-alert-box {
            padding: 10px 14px;
            border-radius: var(--radius-control);
            font-size: 0.9375rem;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .auth-alert-error   { background-color: #FEE2E2; border: var(--rule-hair) solid #FCA5A5; color: #991B1B; }
        .auth-alert-success { background-color: #ECFDF5; border: var(--rule-hair) solid #86EFAC; color: #166534; }

        /* SSO button */
        .btn-sso {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 56px;
            padding: 13px 20px;
            border-radius: var(--radius-pill);
            border: var(--rule-hair) solid rgba(0,98,154,0.30);
            background: linear-gradient(180deg, var(--btn-primary-from), var(--btn-primary-to));
            color: var(--btn-primary-ink);
            box-shadow:
                0 2px 8px rgba(0,119,182,0.26),
                0 6px 20px -4px rgba(0,119,182,0.20),
                inset 0 1px 0 rgba(255,255,255,0.22);
            font-size: 1.0625rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            text-decoration: none;
            transition: transform 180ms var(--ease-out), box-shadow 180ms var(--ease-out);
        }

        .btn-sso:hover {
            background: linear-gradient(180deg, var(--btn-primary-hover-from), var(--btn-primary-hover-to));
            transform: translateY(-2px);
            box-shadow:
                0 4px 14px rgba(0,119,182,0.34),
                0 10px 28px -6px rgba(0,119,182,0.24),
                inset 0 1px 0 rgba(255,255,255,0.28);
        }

        .btn-sso:active { transform: scale(0.97); }

        .btn-sso:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 3px;
        }

        /* SSO icon */
        .sso-icon {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            opacity: 0.90;
        }

        /* divider */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8125rem;
            color: var(--color-muted);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-soft), transparent);
        }

        /* form */
        .auth-form-local {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .form-label {
            display: block;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-ink);
            margin-bottom: 4px;
        }

        .input-wrapper { position: relative; }

        .form-input-modern {
            width: 100%;
            min-height: 56px;
            padding: 10px 48px 10px 16px;
            border: var(--rule-hair) solid var(--border-input);
            border-radius: var(--radius-control);
            background-color: var(--color-paper-hi);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            transition: border-color 160ms var(--ease-out), box-shadow 160ms var(--ease-out);
        }

        .form-input-modern::placeholder { color: var(--color-muted); }

        .form-input-modern:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: -2px;
            border-color: var(--color-focus);
            box-shadow: 0 0 0 4px rgba(0,119,182,0.12);
        }

        .input-trailing-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            min-width: 44px;
            min-height: 44px;
            border: none;
            background: transparent;
            color: var(--color-ink-2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-pill);
            transition: color 160ms var(--ease-out), background-color 160ms var(--ease-out);
        }
        .input-trailing-btn:hover { background-color: var(--border-soft); color: var(--color-ink); }

        .form-check-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9375rem;
            color: var(--color-ink);
        }

        .form-checkbox {
            width: 20px;
            height: 20px;
            accent-color: var(--btn-primary-from);
        }

        /* secondary submit */
        .btn-submit-local {
            width: 100%;
            min-height: 56px;
            padding: 13px 20px;
            border-radius: var(--radius-pill);
            border: 1.5px solid var(--border-input);
            background-color: var(--color-paper-hi);
            color: var(--color-ink);
            font-family: var(--font-body);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(15,27,45,0.06);
            transition: transform 180ms var(--ease-out), background-color 180ms var(--ease-out), box-shadow 180ms var(--ease-out);
        }

        .btn-submit-local:hover {
            background-color: var(--color-paper-2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15,27,45,0.10);
        }

        .btn-submit-local:active { transform: scale(0.97); }

        .btn-submit-local:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        .field-error {
            display: block;
            margin-top: 4px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #991B1B;
        }

        /* footer */
        .auth-footer-clean {
            text-align: center;
            font-size: 0.8125rem;
            color: var(--color-muted);
        }

        /* ─── reduced motion ─────────────────────────────────────── */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <main class="login-split-root">

        {{-- ═══ Left: brand panel ═══ --}}
        <section class="panel-brand" aria-label="Informasi Sistem ELKA">
            <div class="brand-inner">

                {{-- logo + agency name --}}
                <div class="brand-logo-lockup">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" alt="Logo BPS Kabupaten Tegal" class="brand-logo-img">
                    @else
                        <svg class="brand-logo-img" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                            <rect x="1.5" y="1.5" width="33" height="33" rx="8" fill="rgba(255,255,255,0.14)" stroke="rgba(255,255,255,0.40)" stroke-width="1.5"/>
                            <path d="M11 8.5h9.5L25 13v14.5H11V8.5z" stroke="rgba(255,255,255,0.80)" stroke-width="1.75" stroke-linejoin="round"/>
                            <path d="M20.5 8.5V13H25" stroke="rgba(255,255,255,0.80)" stroke-width="1.75" stroke-linejoin="round"/>
                            <rect x="14" y="17.5" width="8" height="3" rx="1.5" fill="#EB891B"/>
                        </svg>
                    @endif
                    <div class="brand-agency-block">
                        <span class="brand-agency-name">BPS Kabupaten Tegal</span>
                        <span class="brand-agency-sub">Perpustakaan Bukti Dukung Kantor</span>
                    </div>
                </div>

                {{-- big wordmark --}}
                <div class="brand-wordmark-block">
                    <h1 class="brand-system-name">ELKA</h1>
                    <p class="brand-tagline">
                        Sistem terpadu penyimpanan, pencarian, dan pemeriksaan dokumen bukti dukung enam Lembar Kerja BPS Kabupaten Tegal.
                    </p>
                </div>

                {{-- feature list --}}
                <ul class="brand-features" aria-label="Fitur utama">
                    <li class="brand-feature">
                        <span class="brand-feature-icon" aria-hidden="true">
                            <svg width="20" height="20" fill="none" stroke="rgba(255,255,255,0.90)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                            </svg>
                        </span>
                        <div class="brand-feature-text">
                            <span class="brand-feature-title">Cari Cepat</span>
                            <span class="brand-feature-desc">Temukan berkas dalam hitungan detik</span>
                        </div>
                    </li>
                    <li class="brand-feature">
                        <span class="brand-feature-icon" aria-hidden="true">
                            <svg width="20" height="20" fill="none" stroke="rgba(255,255,255,0.90)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </span>
                        <div class="brand-feature-text">
                            <span class="brand-feature-title">Cegah Berkas Ganda</span>
                            <span class="brand-feature-desc">Deteksi duplikasi otomatis</span>
                        </div>
                    </li>
                    <li class="brand-feature">
                        <span class="brand-feature-icon" aria-hidden="true">
                            <svg width="20" height="20" fill="none" stroke="rgba(255,255,255,0.90)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M8 7V5a2 2 0 0 0-4 0v2"/>
                            </svg>
                        </span>
                        <div class="brand-feature-text">
                            <span class="brand-feature-title">Terhubung ke Enam LK</span>
                            <span class="brand-feature-desc">Satu portal, semua lembar kerja</span>
                        </div>
                    </li>
                </ul>

                {{-- 3D doc stack --}}
                <div class="login-doc-visual" aria-hidden="true">
                    <div class="login-doc-stack">
                        <span class="login-doc-sheet login-doc-sheet-1"></span>
                        <span class="login-doc-sheet login-doc-sheet-2"></span>
                        <span class="login-doc-sheet login-doc-sheet-3"></span>
                    </div>
                </div>

                {{-- network badge --}}
                <div class="brand-network-badge" role="note" aria-label="Status jaringan">
                    <span class="badge-dot" aria-hidden="true"></span>
                    <span>Jaringan Internal BPS Kabupaten Tegal</span>
                </div>

            </div>
        </section>

        {{-- ═══ Right: auth panel ═══ --}}
        <section class="panel-auth" aria-label="Autentikasi Pengguna ELKA">
            <div class="auth-stage-inner">

                <div class="auth-heading-block">
                    <h2 class="auth-title">Masuk ke ELKA</h2>
                    <p class="auth-subtitle">Silakan masuk memakai akun pegawai BPS atau akun pengelola.</p>
                </div>

                <div class="auth-card">
                    @if (session('error'))
                        <div class="auth-alert-box auth-alert-error" role="alert">
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="auth-alert-box auth-alert-success" role="alert">
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    {{-- primary CTA --}}
                    <a href="{{ route('auth.sso.login') }}" class="btn-sso" id="btn-login-sso">
                        <svg class="sso-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        <span>Login SSO BPS</span>
                    </a>

                    <div class="auth-divider"><span>atau</span></div>

                    <form method="POST" action="{{ route('login', [], false) }}" class="auth-form-local">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email atau NIP Pengelola</label>
                            <input
                                id="email"
                                type="text"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                class="form-input-modern"
                                placeholder="nama@bps.go.id atau 19850101...">
                            @error('email')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Kata Sandi</label>
                            <div class="input-wrapper">
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    class="form-input-modern"
                                    placeholder="Masukkan kata sandi">
                                <button
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Tampilkan kata sandi"
                                    class="input-trailing-btn">
                                    <svg id="eyeShow" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.522 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.478 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg id="eyeHide" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.584 10.587A2 2 0 0013.413 13.4M9.88 5.09A9.77 9.77 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.79 9.79 0 01-4.132 5.411M6.228 6.232A9.785 9.785 0 002.458 12c1.274 4.057 5.064 7 9.542 7a9.77 9.77 0 005.147-1.462" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-check-row">
                            <input type="checkbox" name="remember" id="remember" class="form-checkbox">
                            <label for="remember">Ingat saya di peramban ini</label>
                        </div>

                        <button type="submit" class="btn-submit-local" id="btn-login-local">
                            Masuk sebagai Pengelola
                        </button>
                    </form>
                </div>

                <footer class="auth-footer-clean">
                    &copy; {{ date('Y') }} BPS Kabupaten Tegal
                </footer>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeShow = document.getElementById('eyeShow');
            const eyeHide = document.getElementById('eyeHide');

            if (toggle && passwordInput && eyeShow && eyeHide) {
                toggle.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    eyeShow.style.display = isPassword ? 'none' : 'block';
                    eyeHide.style.display = isPassword ? 'block' : 'none';
                    toggle.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                });
            }
        });
    </script>
</body>
</html>
