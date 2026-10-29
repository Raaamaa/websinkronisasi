@extends('layouts.app')

@section('title', 'Kelola bukti dukung')

@section('content')
    <section class="page-heading">
        <div><p class="eyebrow">ADMINISTRASI</p><h1>Bukti dukung</h1><p class="muted">File hanya dibaca sementara untuk menghitung hash; isi file tidak disimpan di aplikasi.</p></div>
    </section>
    <section class="panel">
        <h2>Daftarkan dokumen</h2>
        <form method="post" action="{{ route('manage.evidence.store') }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="field"><label for="title">Judul dokumen</label><input id="title" name="title" value="{{ old('title') }}" required maxlength="255"></div>
            <div class="field"><label for="document_number">Nomor dokumen <span class="muted">(opsional)</span></label><input id="document_number" name="document_number" value="{{ old('document_number') }}" maxlength="255"></div>
            <div class="field"><label for="lk_id">Lembar Kerja tujuan</label><select id="lk_id" name="lk_id" required><option value="">Pilih LK</option>@foreach ($lks as $lk)<option value="{{ $lk->id }}" @selected(old('lk_id') == $lk->id)>{{ $lk->name }} · {{ $lk->scope === 'internal' ? 'Internal' : 'Eksternal' }}</option>@endforeach</select></div>
            <div class="field"><label for="drive_url">Tautan Drive</label><input id="drive_url" name="drive_url" type="url" value="{{ old('drive_url') }}" placeholder="https://drive.google.com/..." required maxlength="2048"></div>
            <div class="field field-wide"><label for="file">File untuk pencocokan</label><input id="file" name="file" type="file" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" required><small>PDF, DOCX, XLSX, JPG/PNG · maksimal 20 MB. File dihapus otomatis setelah request.</small></div>
            <div class="field field-wide"><button class="button button-primary" type="submit">Hitung hash & daftarkan</button></div>
        </form>
    </section>
    <section class="panel">
        <div class="section-title"><h2>Dokumen terdaftar</h2><span class="muted">{{ $evidences->total() }} catatan</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Dokumen</th><th>LK</th><th>SHA-256</th><th>Drive</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($evidences as $evidence)
                <tr>
                    <td><strong>{{ $evidence->title }}</strong><small class="table-subhead">{{ $evidence->original_filename }} · {{ $evidence->uploader?->username ?? 'Akun dihapus' }}</small></td>
                    <td>{{ $evidence->lk->name }}</td>
                    <td><code>{{ substr($evidence->sha256, 0, 12) }}…</code></td>
                    <td><a href="{{ $evidence->drive_url }}" target="_blank" rel="noopener noreferrer">Buka</a></td>
                    <td><form method="post" action="{{ route('manage.evidence.destroy', $evidence) }}" onsubmit="return confirm('Hapus catatan bukti ini?')">@csrf @method('delete')<button class="button button-danger button-small" type="submit">Hapus</button></form></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">Belum ada bukti dukung.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $evidences->links() }}
    </section>
@endsection
