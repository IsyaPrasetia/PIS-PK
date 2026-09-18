@extends('layouts.app')

@section('page-title', 'Tambah dengan AI')
@section('title', 'Tambah dengan Bantuan AI')
@section('subtitle', 'Tempel catatan hasil kunjungan, biarkan AI menyusun data terstruktur.')

@section('tabs')
    @include('partials.input-tabs', ['active' => 'ai'])
@endsection

@section('content')
    <form method="POST" action="{{ route('ai.extract') }}">
        @csrf
        <div class="ai-box">
            <h3>&#10022; Tempel catatan kunjungan</h3>
            <p>
                Tulis atau tempel catatan bebas dari hasil wawancara/kunjungan rumah (Bahasa Indonesia biasa saja). AI
                lokal akan menyusunnya menjadi data keluarga, anggota, dan 12 indikator secara otomatis — tanpa perlu
                koneksi internet. Anda tetap bisa memeriksa dan mengoreksi sebelum menyimpan.
            </p>
            <textarea name="catatan" placeholder="Contoh: Keluarga Bapak Sutrisno, tinggal di Jl. Anggrek No 12 RT 03 RW 05 Desa Cibiru. Istrinya bernama Wati, 34 tahun, ikut KB suntik. Anak pertama Dimas umur 6 tahun, imunisasi lengkap saat bayi. Anak kedua Nadia umur 1 tahun, masih ASI eksklusif, rutin ditimbang di posyandu. Belum ada anggota keluarga dengan TB, hipertensi, atau gangguan jiwa. Tidak ada yang merokok. Keluarga sudah punya kartu BPJS/JKN. Sumber air dari PDAM, punya jamban sendiri.">{{ old('catatan') }}</textarea>
            @error('catatan')<div class="ai-status error">{{ $message }}</div>@enderror
            <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <button type="submit" class="btn">Proses dengan AI</button>
                <a href="{{ route('families.create') }}" class="btn ghost">Isi Manual Saja</a>
            </div>
            <p style="color:var(--ink-soft);font-size:12px;margin-top:10px;">
                AI berjalan di mesin lokal unit kerja (pola kata &amp; aturan kesehatan), tanpa mengirim data ke luar.
            </p>
        </div>
    </form>
@endsection
