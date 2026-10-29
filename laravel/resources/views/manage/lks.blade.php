@extends('layouts.app')

@section('title', 'Kelola Lembar Kerja')

@section('content')
    <section class="page-heading"><div><p class="eyebrow">SUPER ADMIN</p><h1>Lembar Kerja</h1><p class="muted">Daftar LK yang digunakan untuk mencocokkan bukti dukung.</p></div></section>
    <section class="panel">
        <h2>Tambah LK</h2>
        <form method="post" action="{{ route('manage.lks.store') }}" class="form-grid compact-grid">
            @csrf
            <div class="field"><label for="name">Nama LK</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="100"></div>
            <div class="field"><label for="scope">Kelompok</label><select id="scope" name="scope" required><option value="internal">Internal</option><option value="external">Eksternal</option></select></div>
            <div class="field field-end"><button class="button button-primary" type="submit">Tambah LK</button></div>
        </form>
    </section>
    <section class="panel">
        <h2>Daftar LK</h2>
        <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Kelompok</th><th>Bukti terdaftar</th><th>Aksi</th></tr></thead><tbody>
        @foreach ($lks as $lk)
            <tr><td><strong>{{ $lk->name }}</strong></td><td>{{ $lk->scope === 'internal' ? 'Internal' : 'Eksternal' }}</td><td>{{ $lk->evidences_count }}</td><td><form method="post" action="{{ route('manage.lks.destroy', $lk) }}" onsubmit="return confirm('Hapus LK ini?')">@csrf @method('delete')<button class="button button-danger button-small" type="submit">Hapus</button></form></td></tr>
        @endforeach
        </tbody></table></div>
    </section>
@endsection
