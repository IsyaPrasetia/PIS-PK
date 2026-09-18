@extends('layouts.app')

@section('page-title', 'Impor Excel')
@section('title', 'Impor Data Keluarga dari Excel')
@section('subtitle', 'Unggah file Excel/CSV berisi banyak keluarga sekaligus.')

@section('tabs')
    @include('partials.input-tabs', ['active' => 'excel'])
@endsection

@section('content')
    <div id="impor-app"
        data-store-url="{{ route('impor.store') }}"
        data-index-url="{{ route('families.index') }}"
        data-indicator-ids="{{ implode(',', \App\Support\Indikator::ids()) }}">

        <div class="excel-box">
            <h3>&#128196; Impor banyak keluarga sekaligus dari Excel</h3>
            <p>
                Unggah file Excel (.xlsx/.xls) atau CSV berisi data beberapa keluarga. Gunakan template agar nama/urutan kolom
                sesuai — setiap baris akan dipetakan otomatis menjadi data keluarga, anggota, dan 12 indikator. Periksa hasilnya
                sebelum menyimpan.
            </p>
            <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <button type="button" class="btn ghost small" id="excel-template">&#11015; Unduh Template Excel</button>
            </div>
            <label class="excel-dropzone" for="excel-file">
                <div class="big">&#128194;</div>
                <div><strong>Klik untuk pilih file Excel/CSV</strong></div>
                <div style="color:var(--ink-soft);font-size:12.5px;margin-top:4px;">.xlsx, .xls, atau .csv &mdash; baris pertama dianggap header kolom</div>
            </label>
            <input type="file" id="excel-file" accept=".xlsx,.xls,.csv" style="display:none;">
            <div class="excel-filename is-hidden" id="excel-filename"></div>
            <div class="ai-status error is-hidden" id="excel-error"></div>
            <div style="margin-top:14px;" id="excel-process-wrap">
                <button type="button" class="btn" id="excel-process">Proses File</button>
            </div>
        </div>

        <div class="card is-hidden" id="excel-result">
            <h3>Hasil pemetaan — silakan periksa sebelum disimpan</h3>
            <p style="color:var(--ink-soft);font-size:13px;" id="excel-result-summary"></p>
            <div id="excel-drafts"></div>
            <div class="form-actions" style="position:static;background:none;">
                <button type="button" class="btn" id="excel-save-all">Simpan ke Data Keluarga</button>
                <button type="button" class="btn ghost" id="excel-discard">Batalkan Impor</button>
            </div>
        </div>
    </div>
@endsection
