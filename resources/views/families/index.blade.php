@extends('layouts.app')

@section('page-title', 'Data Keluarga')
@section('title', 'Data Keluarga')
@section('subtitle', 'Daftar keluarga yang telah didata, lengkap dengan status IKS.')

@section('topbar-action')
    @if (auth()->user()->canCreateFamily())
        <a href="{{ route('families.create') }}" class="btn">+ Tambah Keluarga</a>
    @endif
@endsection

@section('content')
    <form method="GET" action="{{ route('families.index') }}" class="card indikator-filters" id="indikator-filters" style="margin-bottom:16px;">
        <div class="field" style="margin:0;display:flex;flex-direction:column;align-items:flex-start;">
            <label for="f-q">Cari</label>
            <div style="display:flex;gap:8px;width:100%;max-width:360px;">
                <div style="flex:1;">
                    <input type="search" id="f-q" name="q" placeholder="Nama, alamat, atau no. KK..." value="{{ $term }}">
                </div>
            </div>
        </div>

        <div class="field" style="margin:0;" data-filter-wrap="kecamatan">
            <label for="f-kecamatan">Kecamatan</label>
            <select id="f-kecamatan" name="kecamatan" data-wilayah-filter="kecamatan">
                <option value="">Semua Kecamatan</option>
                @foreach (array_keys($wilayahTree) as $kecamatan)
                    <option value="{{ $kecamatan }}" @selected($filters['kecamatan'] === $kecamatan)>{{ $kecamatan }}</option>
                @endforeach
            </select>
        </div>

        <div class="field" style="margin:0;{{ $filters['kecamatan'] === '' ? 'display:none;' : '' }}" data-filter-wrap="desa">
            <label for="f-desa">Desa/Kelurahan</label>
            <select id="f-desa" name="desa" data-wilayah-filter="desa">
                <option value="">Semua Desa</option>
                @if ($filters['kecamatan'] !== '' && isset($wilayahTree[$filters['kecamatan']]))
                    @foreach (array_keys($wilayahTree[$filters['kecamatan']]) as $desa)
                        <option value="{{ $desa }}" @selected($filters['desa'] === $desa)>{{ $desa }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="field" style="margin:0;{{ $filters['kecamatan'] === '' || $filters['desa'] === '' ? 'display:none;' : '' }}" data-filter-wrap="rw">
            <label for="f-rw">RW</label>
            <select id="f-rw" name="rw" data-wilayah-filter="rw">
                <option value="">Semua RW</option>
                @if ($filters['kecamatan'] !== '' && $filters['desa'] !== '' && isset($wilayahTree[$filters['kecamatan']][$filters['desa']]))
                    @foreach (array_keys($wilayahTree[$filters['kecamatan']][$filters['desa']]) as $rw)
                        <option value="{{ $rw }}" @selected($filters['rw'] === $rw)>{{ $rw }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="field" style="margin:0;{{ $filters['kecamatan'] === '' || $filters['desa'] === '' || $filters['rw'] === '' ? 'display:none;' : '' }}" data-filter-wrap="rt">
            <label for="f-rt">RT</label>
            <select id="f-rt" name="rt" data-wilayah-filter="rt">
                <option value="">Semua RT</option>
                @if ($filters['kecamatan'] !== '' && $filters['desa'] !== '' && $filters['rw'] !== '' && isset($wilayahTree[$filters['kecamatan']][$filters['desa']][$filters['rw']]))
                    @foreach ($wilayahTree[$filters['kecamatan']][$filters['desa']][$filters['rw']] as $rt)
                        <option value="{{ $rt }}" @selected($filters['rt'] === $rt)>{{ $rt }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="field" style="margin:0;">
            <label for="f-per-page">Per Halaman</label>
            <select id="f-per-page" name="per_page" data-per-page>
                <option value="50" @selected($perPage === 50)>50</option>
                <option value="75" @selected($perPage === 75)>75</option>
                <option value="100" @selected($perPage === 100)>100</option>
            </select>
        </div>

        <div class="field-actions" style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn primary small">Cari</button>
            <a href="{{ route('families.index') }}" class="btn ghost small">Reset</a>
        </div>
    </form>

    <div class="card" style="padding:0;overflow-x:auto;">
        @if ($families->isEmpty())
            <div class="empty">
                <div class="big">&#128269;</div>
                {{ $term !== '' ? 'Tidak ada keluarga yang cocok.' : 'Belum ada data keluarga.' }}
            </div>
        @else
            <table>
                <thead>
                    <tr><th>Kepala Keluarga</th><th>Alamat</th><th>Anggota</th><th>Status IKS</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($families as $family)
                        @php $iks = $family->iks(); @endphp
                        <tr>
                            <td>
                                <strong>{{ $family->kepala_keluarga ?: '(tanpa nama)' }}</strong>
                                @if ($family->no_kk)
                                    <br><span style="color:var(--ink-soft);font-size:12px;">No. KK: {{ $family->no_kk }}</span>
                                @endif
                            </td>
                            <td>{{ $family->alamatLengkap() ?: '—' }}</td>
                            <td>{{ $family->members_count }} orang</td>
                            <td>
                                <span class="badge {{ $iks['class'] }}">{{ $iks['label'] }}@if ($iks['score'] !== null) &middot; {{ round($iks['score'] * 100).'%' }}@endif</span>
                                @php
                                    $seg = \App\Support\Segmentasi::analisis($family);
                                    $segmentsList = $seg['segments'];
                                @endphp
                                <div style="margin-top:6px;line-height:1.8;">
                                    @foreach (array_slice($segmentsList, 0, 4) as $segId)
                                        <span class="seg-chip">{{ \App\Support\Segmentasi::label($segId) }}</span>
                                    @endforeach
                                    @if ($seg['requires_human_review'])
                                        <span class="seg-flag">perlu tinjauan &middot; prioritas {{ $seg['priority_level_label'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="row-actions">
                                    @if (auth()->user()->canEditFamily($family))
                                        <a href="{{ route('families.edit', $family) }}" class="btn ghost small">Ubah</a>
                                        <button type="button" class="btn danger small"
                                            data-delete-url="{{ route('families.destroy', $family) }}"
                                            data-delete-name="{{ $family->kepala_keluarga ?: 'ini' }}">Hapus</button>
                                    @elseif (auth()->user()->isAdmin())
                                        <a href="{{ route('families.edit', $family) }}" class="btn ghost small">Ubah</a>
                                    @else
                                        <span style="color:var(--ink-soft);font-size:12px;">Dikunci (menunggu admin)</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="table-footer" style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-top:1px solid var(--line);">
                <span style="color:var(--ink-soft);font-size:12.5px;">
                    Menampilkan {{ $families->firstItem() }}&ndash;{{ $families->lastItem() }} dari {{ $families->total() }} keluarga.
                </span>
                <div>
                    {{ $families->links() }}
                </div>
            </div>
        @endif
    </div>

    <script type="application/json" id="wilayah-tree-data">@json($wilayahTree)</script>
    <script>
        (function () {
            var tree = {};
            try { tree = JSON.parse(document.getElementById('wilayah-tree-data').textContent); } catch (e) {}

            var form = document.getElementById('indikator-filters');
            var kecSel = document.getElementById('f-kecamatan');
            var desaSel = document.getElementById('f-desa');
            var rwSel = document.getElementById('f-rw');
            var rtSel = document.getElementById('f-rt');
            var wrapDesa = form.querySelector('[data-filter-wrap="desa"]');
            var wrapRw = form.querySelector('[data-filter-wrap="rw"]');
            var wrapRt = form.querySelector('[data-filter-wrap="rt"]');

            function show(el) { el.style.display = 'block'; }
            function hide(el) { el.style.display = 'none'; }

            function buildOptions(vals, placeholder) {
                return '<option value="">' + placeholder + '</option>' + vals.map(function (v) {
                    return '<option value="' + v + '">' + v + '</option>';
                }).join('');
            }

            function onKecamatan() {
                var kec = kecSel.value;
                hide(wrapRw); hide(wrapRt);
                if (!kec) { hide(wrapDesa); return; }
                show(wrapDesa);
                var desaList = tree[kec] ? Object.keys(tree[kec]) : [];
                desaSel.innerHTML = buildOptions(desaList, 'Semua Desa');
            }

            function onDesa() {
                var kec = kecSel.value, desa = desaSel.value;
                hide(wrapRt);
                if (!desa) { hide(wrapRw); return; }
                show(wrapRw);
                var rwList = kec && tree[kec] && tree[kec][desa] ? Object.keys(tree[kec][desa]) : [];
                rwSel.innerHTML = buildOptions(rwList, 'Semua RW');
            }

            function onRw() {
                var kec = kecSel.value, desa = desaSel.value, rw = rwSel.value;
                if (!rw) { hide(wrapRt); return; }
                show(wrapRt);
                var rtList = kec && desa && tree[kec][desa] && tree[kec][desa][rw] ? tree[kec][desa][rw] : [];
                rtSel.innerHTML = buildOptions(rtList, 'Semua RT');
            }

            kecSel.addEventListener('change', onKecamatan);
            desaSel.addEventListener('change', onDesa);
            rwSel.addEventListener('change', onRw);
            rtSel.addEventListener('change', function () {});
        })();
    </script>
@endsection
