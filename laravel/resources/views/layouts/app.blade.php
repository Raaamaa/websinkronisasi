<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SyncHub') · {{ config('app.name', 'SyncHub') }}</title>
    <link rel="stylesheet" href="{{ asset('css/synchub.css') }}">
    @livewireStyles
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('search') }}"><span class="brand-icon">S</span><span>SyncHub<small>Matching bukti dukung</small></span></a>
    @auth
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="{{ route('search') }}">Cari dokumen</a>
            @if (auth()->user()->canManageEvidence())
                <a href="{{ route('manage.evidence.index') }}">Bukti dukung</a>
            @endif
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('manage.lks.index') }}">Lembar Kerja</a>
                <a href="{{ route('manage.users.index') }}">Akun</a>
            @endif
        </nav>
        <div class="user-menu">
            <span>{{ auth()->user()->name }} <small>{{ str_replace('_', ' ', auth()->user()->role) }}</small></span>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="button button-quiet" type="submit">Keluar</button></form>
        </div>
    @endauth
</header>
<main class="page">
    @if (session('status'))
        <div class="notice notice-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error" role="alert">
            <strong>Periksa kembali:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
@livewireScripts
</body>
</html>
