@extends('layouts.app')

@section('title', 'Cari bukti dukung')

@section('content')
    <section class="page-heading">
        <div><p class="eyebrow">KATALOG BUKTI DUKUNG</p><h1>Cari kecocokan dokumen</h1><p class="muted">Cari berdasarkan judul, nomor dokumen, atau nama file.</p></div>
    </section>
    <livewire:evidence-search />
@endsection
