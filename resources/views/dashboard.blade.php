@extends('layouts.app')

@section('page-title', 'Dasbor')
@section('title', 'Dasbor Keluarga Sehat')
@section('subtitle', 'Ringkasan capaian 12 indikator PIS-PK di wilayah kerja Anda.')

@section('content')
    @if ($families->isEmpty())
        <div class="empty card">
            <div class="big">&#127968;</div>
            <h3>Belum ada data keluarga</h3>
            <p>Mulai dengan menambahkan data secara manual atau gunakan Asisten AI untuk mempercepat input.</p>
            <div style="margin-top:14px;display:flex;gap:10px;justify-content:center;">
                <a href="{{ route('ai.index') }}" class="btn">Coba Input dengan AI</a>
                <a href="{{ route('families.create') }}" class="btn ghost">Tambah Manual</a>
            </div>
        </div>
    @else
        <div class="grid-cards">
            <div class="stat-card"><div class="num">{{ $stats['n'] }}</div><div class="lbl">Total Keluarga Didata</div></div>
            <div class="stat-card"><div class="num">{{ $stats['avg'] !== null ? round($stats['avg'] * 100).'%' : '—' }}</div><div class="lbl">Rata-rata Indeks Keluarga Sehat (IKS)</div></div>
            <div class="stat-card"><div class="num" style="color:var(--success)">{{ $stats['sehat'] }}</div><div class="lbl">Keluarga Sehat (IKS &gt; 0.8)</div></div>
            <div class="stat-card"><div class="num" style="color:var(--amber)">{{ $stats['pra'] }}</div><div class="lbl">Pra-Sehat (0.5&ndash;0.8)</div></div>
            <div class="stat-card"><div class="num" style="color:var(--clay)">{{ $stats['tidak'] }}</div><div class="lbl">Tidak Sehat (&lt; 0.5)</div></div>
        </div>

        <div class="card" style="margin-bottom:16px;" data-wilayah-app>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <h3 style="margin:0;">Rekap &amp; Grafik per Wilayah</h3>
                <div class="import-tabs" style="margin:0;">
                    @foreach ($wilayah as $level => $data)
                        <button type="button" class="import-tab {{ $loop->first ? 'active' : '' }}" data-wilayah-level="{{ $level }}">{{ $data['label'] }}</button>
                    @endforeach
                </div>
            </div>
            <p style="color:var(--ink-soft);font-size:12.5px;margin:10px 0 0 0;">
                Rata-rata Indeks Keluarga Sehat (IKS) dan jumlah KK per jenjang wilayah. Pilih jenjang untuk mengubah grafik dan tabel.
            </p>

            <div class="chart-scroll">
                <div id="wilayah-chart"></div>
            </div>

            @foreach ($wilayah as $level => $data)
                <div data-wilayah-panel="{{ $level }}" class="{{ $loop->first ? '' : 'is-hidden' }}" style="margin-top:16px;">
                    <table>
                        <thead>
                            <tr><th>Wilayah</th><th>Jml KK</th><th>Rata-rata IKS</th><th>Sehat</th><th>Pra-Sehat</th><th>Tidak Sehat</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($data['rows'] as $row)
                                @php
                                    $avg = $row['avg'];
                                    $cls = $avg === null ? 'na' : ($avg > 0.8 ? 'sehat' : ($avg >= 0.5 ? 'pra' : 'tidak'));
                                @endphp
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['n'] }}</td>
                                    <td><span class="badge {{ $cls }}">{{ $avg === null ? '—' : round($avg * 100).'%' }}</span></td>
                                    <td>{{ $row['sehat'] }}</td>
                                    <td>{{ $row['pra'] }}</td>
                                    <td>{{ $row['tidak'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="text-align:center;color:var(--ink-soft);padding:20px;">Belum ada data wilayah.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        <div class="card" data-indikator-app>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
                <div>
                    <h3 style="margin:0;">Capaian per Indikator</h3>
                    <p style="color:var(--ink-soft);font-size:13px;margin:6px 0 0 0;">Persentase "Ya" dari keluarga yang indikator tersebut berlaku.</p>
                </div>
                <div class="import-tabs" style="margin:0;" data-indikator-mode-tabs>
                    <button type="button" class="import-tab active" data-indikator-mode="batang">Grafik Batang</button>
                    <button type="button" class="import-tab" data-indikator-mode="kartesius">Kartesius</button>
                </div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="indikator-filters" id="indikator-filters">
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
                <div class="field" style="margin:0;{{ $filters['desa'] === '' ? 'display:none;' : '' }}" data-filter-wrap="rw">
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
                <div class="field" style="margin:0;{{ $filters['rw'] === '' ? 'display:none;' : '' }}" data-filter-wrap="rt">
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
                    <label for="f-domain">Kelompok Indikator</label>
                    <select id="f-domain" name="domain">
                        <option value="">All Indikator</option>
                        @foreach (\App\Support\Indikator::domains() as $domain)
                            <option value="{{ $domain }}" @selected($filters['domain'] === $domain)>{{ $domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-actions" style="display:flex;gap:8px;align-items:flex-end;">
                    <button type="submit" class="btn primary small">Cari</button>
                    <a href="{{ route('dashboard') }}" class="btn ghost small">Reset</a>
                </div>
            </form>

            <div data-indikator-panel="batang">
                <div class="bars">
                    @forelse ($indicators as $indicator)
                        @php
                            $color = $indicator['pct'] === null ? '#ccc' : ($indicator['pct'] > 0.8 ? 'var(--success)' : ($indicator['pct'] >= 0.5 ? 'var(--amber)' : 'var(--clay)'));
                        @endphp
                        <div class="bar-row">
                            <div class="name">{{ $indicator['q'] }}</div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:{{ $indicator['pct'] === null ? 0 : round($indicator['pct'] * 100) }}%;background:{{ $color }}"></div>
                            </div>
                            <div class="pct">{{ $indicator['pct'] === null ? 'n/a' : round($indicator['pct'] * 100).'%' }}</div>
                        </div>
                    @empty
                        <p style="color:var(--ink-soft);font-size:13px;margin:14px 0 0 0;">Tidak ada indikator pada kelompok ini.</p>
                    @endforelse
                </div>
            </div>

            <div data-indikator-panel="kartesius" class="is-hidden">
                <div class="chart-scroll"><div id="indikator-chart"></div></div>
            </div>
        </div>

        <script type="application/json" id="wilayah-data">@json($wilayah)</script>
        <script type="application/json" id="indikator-data">@json($indikatorChart)</script>
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
    @endif
@endsection
