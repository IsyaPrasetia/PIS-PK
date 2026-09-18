@extends('layouts.app')

@section('page-title', 'Master Wilayah')
@section('title', 'Master Wilayah')
@section('subtitle', 'Data kecamatan, desa/kelurahan, RW, dan RT yang dipakai untuk dropdown wilayah.')

@section('content')
    <div class="card" style="max-width:860px;">
        <div class="section-title">Tambah Wilayah</div>
        <p style="color:var(--ink-soft);font-size:13px;margin-top:-4px;">
            Pilih level yang ingin ditambahkan, lalu isi nama/nomornya. Bisa juga diisi otomatis dari Impor Excel.
        </p>

        <form method="POST" action="{{ route('wilayah.store') }}" id="wilayah-form">
            @csrf

            <div class="field">
                <label for="level">Level Wilayah</label>
                <select id="level" name="level" required>
                    <option value="kecamatan" @selected(old('level') === 'kecamatan')>Kecamatan</option>
                    <option value="desa" @selected(old('level') === 'desa')>Desa/Kelurahan</option>
                    <option value="rw" @selected(old('level') === 'rw')>RW</option>
                    <option value="rt" @selected(old('level', 'rt') === 'rt')>RT</option>
                </select>
            </div>

            <div class="field" id="wrap-kecamatan">
                <label for="kecamatan">Kecamatan</label>
                @if ($isSuperadmin)
                    <select id="kecamatan" name="kecamatan">
                        <option value="">— Pilih Kecamatan —</option>
                        @foreach ($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan }}" @selected(old('kecamatan') === $kecamatan)>{{ $kecamatan }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" value="{{ auth()->user()->kecamatan }}" readonly>
                    <input type="hidden" id="kecamatan" name="kecamatan" value="{{ auth()->user()->kecamatan }}">
                @endif
            </div>

            <div class="field" id="wrap-desa">
                <label for="desa">Desa/Kelurahan</label>
                <select id="desa" name="desa">
                    <option value="">— Pilih Desa/Kelurahan —</option>
                </select>
            </div>

            <div class="field" id="wrap-rw">
                <label for="rw">RW</label>
                <select id="rw" name="rw">
                    <option value="">— Pilih RW —</option>
                </select>
            </div>

            <div class="field">
                <label for="nama" id="label-nama">Nama / Nomor Baru</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" placeholder="Contoh: Cibiru, 05, 03" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Tambah Wilayah</button>
            </div>
        </form>
    </div>

    <div class="card" style="max-width:860px;">
        <div class="section-title">Daftar Wilayah</div>

        @if (empty($tree))
            <div class="empty">
                <div class="big">&#127968;</div>
                Belum ada data wilayah.
            </div>
        @else
            @foreach ($tree as $kecamatan => $desas)
                <div style="margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <strong style="font-size:15px;">{{ $kecamatan }}</strong>
                        <form method="POST" action="{{ route('wilayah.destroy') }}" data-confirm="Hapus kecamatan {{ $kecamatan }} beserta seluruh isinya?">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="level" value="kecamatan">
                            <input type="hidden" name="kecamatan" value="{{ $kecamatan }}">
                            <button type="submit" class="btn danger small">Hapus Kecamatan</button>
                        </form>
                    </div>

                    @forelse ($desas as $desa => $rws)
                        <div style="margin:8px 0 0 16px;">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <span>{{ $desa }}</span>
                                <form method="POST" action="{{ route('wilayah.destroy') }}" data-confirm="Hapus desa/kelurahan {{ $desa }}?">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="level" value="desa">
                                    <input type="hidden" name="kecamatan" value="{{ $kecamatan }}">
                                    <input type="hidden" name="desa" value="{{ $desa }}">
                                    <button type="submit" class="btn ghost small">Hapus</button>
                                </form>
                            </div>

                            @forelse ($rws as $rw => $rts)
                                <div style="margin:6px 0 0 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <span>RW {{ $rw }}</span>
                                    <span style="color:var(--ink-soft);font-size:12.5px;">
                                        RT: {{ $rts === [] ? '—' : implode(', ', $rts) }}
                                    </span>
                                    <form method="POST" action="{{ route('wilayah.destroy') }}" data-confirm="Hapus RW {{ $rw }}?">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="level" value="rw">
                                        <input type="hidden" name="kecamatan" value="{{ $kecamatan }}">
                                        <input type="hidden" name="desa" value="{{ $desa }}">
                                        <input type="hidden" name="rw" value="{{ $rw }}">
                                        <button type="submit" class="btn ghost small">Hapus</button>
                                    </form>
                                </div>
                            @empty
                                <div style="margin:6px 0 0 16px;color:var(--ink-soft);font-size:12.5px;">Belum ada RW.</div>
                            @endforelse
                        </div>
                    @empty
                        <div style="margin:8px 0 0 16px;color:var(--ink-soft);font-size:12.5px;">Belum ada desa/kelurahan.</div>
                    @endforelse
                </div>
            @endforeach
        @endif
    </div>

    <script type="application/json" id="wilayah-tree-data">@json($tree)</script>

    <script>
        (function () {
            var tree = {};
            try { tree = JSON.parse(document.getElementById('wilayah-tree-data').textContent); } catch (e) {}

            var levelSel = document.getElementById('level');
            var kecSel = document.getElementById('kecamatan');
            var desaSel = document.getElementById('desa');
            var rwSel = document.getElementById('rw');
            var wrapKec = document.getElementById('wrap-kecamatan');
            var wrapDesa = document.getElementById('wrap-desa');
            var wrapRw = document.getElementById('wrap-rw');
            var labelNama = document.getElementById('label-nama');
            var namaInput = document.getElementById('nama');

            var labels = {
                kecamatan: 'Nama Kecamatan Baru',
                desa: 'Nama Desa/Kelurahan Baru',
                rw: 'Nomor RW Baru',
                rt: 'Nomor RT Baru'
            };

            function fill(select, values) {
                var current = select.value;
                select.innerHTML = '<option value="">— Pilih —</option>' + values.map(function (v) {
                    return '<option value="' + v + '"' + (v === current ? ' selected' : '') + '>' + v + '</option>';
                }).join('');
            }

            function refresh() {
                var level = levelSel.value;
                var kec = kecSel ? kecSel.value : '';
                var desa = desaSel.value;

                labelNama.textContent = labels[level] || 'Nama';

                wrapKec.style.display = level === 'kecamatan' ? 'none' : 'block';
                wrapDesa.style.display = (level === 'rw' || level === 'rt') ? 'block' : 'none';
                wrapRw.style.display = level === 'rt' ? 'block' : 'none';

                var desaList = kec && tree[kec] ? Object.keys(tree[kec]) : [];
                fill(desaSel, desaList);

                var rwList = kec && desa && tree[kec][desa] ? Object.keys(tree[kec][desa]) : [];
                fill(rwSel, rwList);
            }

            levelSel.addEventListener('change', refresh);
            if (kecSel) kecSel.addEventListener('change', refresh);
            desaSel.addEventListener('change', refresh);

            refresh();
        })();
    </script>
@endsection
