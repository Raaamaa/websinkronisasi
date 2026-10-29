<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name', 'SyncHub') }}</title>
    <link rel="stylesheet" href="{{ asset('css/synchub.css') }}">
</head>
<body class="login-page">
<main class="login-card">
    <a class="brand login-brand" href="{{ route('login') }}"><span class="brand-icon">S</span><span>SyncHub<small>Matching bukti dukung</small></span></a>
    <p class="eyebrow">WORKSPACE INTERNAL</p>
    <h1>Masuk ke workspace</h1>
    <p class="muted">Cari dan kelola bukti dukung dari satu tempat.</p>
    @if ($errors->any())
        <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('login.store') }}" class="stack-form">
        @csrf
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button button-primary button-wide" type="submit">Masuk</button>
    </form>
    <p class="login-footnote">Akses pegawai, admin, dan super admin.</p>
</main>
</body>
</html>
