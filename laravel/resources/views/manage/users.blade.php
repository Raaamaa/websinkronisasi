@extends('layouts.app')

@section('title', 'Kelola akun')

@section('content')
    <section class="page-heading"><div><p class="eyebrow">SUPER ADMIN</p><h1>Kelola akun</h1><p class="muted">Buat akun admin atau pegawai sementara sampai SSO diintegrasikan.</p></div></section>
    <section class="panel">
        <h2>Tambah akun</h2>
        <form method="post" action="{{ route('manage.users.store') }}" class="form-grid">
            @csrf
            <div class="field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="255"></div>
            <div class="field"><label for="username">Username</label><input id="username" name="username" value="{{ old('username') }}" required maxlength="100" autocomplete="off"></div>
            <div class="field"><label for="role">Peran</label><select id="role" name="role" required><option value="employee">Pegawai</option><option value="admin">Admin</option></select></div>
            <div class="field"><label for="password">Password sementara</label><input id="password" name="password" type="password" minlength="12" required autocomplete="new-password"></div>
            <div class="field"><label for="password_confirmation">Ulangi password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password"></div>
            <div class="field field-wide"><button class="button button-primary" type="submit">Buat akun</button></div>
        </form>
        <p class="field-help">Password minimal 12 karakter. Berikan secara pribadi kepada pemilik akun.</p>
    </section>
    <section class="panel">
        <h2>Akun aktif</h2>
        <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($users as $user)
            <tr><td>{{ $user->name }}</td><td>{{ $user->username }}</td><td>{{ $user->role === 'admin' ? 'Admin' : 'Pegawai' }}</td><td><form method="post" action="{{ route('manage.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun ini?')">@csrf @method('delete')<button class="button button-danger button-small" type="submit">Hapus</button></form></td></tr>
        @empty
            <tr><td colspan="4" class="empty-cell">Belum ada akun tambahan.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>
@endsection
