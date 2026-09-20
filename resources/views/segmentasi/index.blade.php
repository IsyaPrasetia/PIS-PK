@extends('layouts.app')

@section('page-title', 'Segmentasi Keluarga')
@section('title', 'Segmentasi Keluarga')
@section('subtitle', 'Pengelompokan keluarga berdasarkan kebutuhan, risiko, dan kualitas data — prioritas tindak lanjut keluarga.')

@section('content')
    <form method="GET" action="{{ route('segmentasi.index') }}" class="card indikator-filters" id="indikator-filters" style="margin-bottom:16px;">
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
            <label for="f-segmen">Segmen</label>
            <select id="f-segmen" name="segmen">
                <option value="">Semua Segmen</option>
                @foreach ($perKelompok as $kelompok => $items)
                    <optgroup label="{{ $kelompok === 'kebutuhan' ? 'Segmen Kebutuhan' : 'Segmen Kualitas Data' }}">
                        @foreach ($items as $item)
                            <option value="{{ $item['id'] }}" @selected($segmenTerpilih === $item['id'])>{{ $item['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="field" style="margin:0;">
            <label for="f-per-page">Per Halaman</label>
            <select id="f-per-page" name="per_page" data-per-page>
                <option value="25" @selected($perPage === 25)>25</option>
                <option value="50" @selected($perPage === 50)>50</option>
                <option value="100" @selected($perPage === 100)>100</option>
            </select>
        </div>

        <div class="field-actions" style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn primary small">Cari</button>
            <a href="{{ route('segmentasi.index') }}" class="btn ghost small">Reset</a>
        </div>
    </form>

    <div class="grid-cards">
        <div class="stat-card"><div class="num">{{ $rekap['n'] }}</div><div class="lbl">Total Keluarga</div></div>
        <div class="stat-card"><div class="num" style="color:var(--clay)">{{ $rekap['perLevel']['tinggi'] + $rekap['perLevel']['sangat_tinggi'] }}</div><div class="lbl">Prioritas Tinggi &amp; Sangat Tinggi</div></div>
        <div class="stat-card"><div class="num" style="color:var(--amber)">{{ $rekap['perluTinjau'] }}</div><div class="lbl">Perlu Tinjauan Petugas</div></div>
        <div class="stat-card"><div class="num" style="color:var(--primary)">{{ $rekap['duplikat'] }}</div><div class="lbl">Duplikasi Potensial</div></div>
    </div>

    @foreach ($perKelompok as $kelompok => $items)
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin:0;">{{ $kelompok === 'kebutuhan' ? 'Segmen Kebutuhan' : 'Segmen Kualitas Data' }}</h3>
            <p style="color:var(--ink-soft);font-size:13px;margin:6px 0 14px 0;">
                {{ $kelompok === 'kebutuhan' ? 'Hal yang mencerminkan kebutuhan atau masalah keluarga. Satu keluarga bisa masuk lebih dari satu segmen.' : 'Kualitas dan keandalan data — terpisah dari segmen kebutuhan agar data kosong tidak dianggap tidak sehat.' }}
            </p>
            <div class="grid-cards" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
                @foreach ($items as $item)
                    <div class="stat-card" style="border-top:3px solid var(--line);">
                        <div class="num" style="font-size:26px;">{{ $rekap['perSegmen'][$item['id']] }}</div>
                        <div class="lbl" style="font-size:13px;">{{ $item['label'] }}</div>
                        <p style="color:var(--ink-soft);font-size:12px;margin:8px 0 0 0;">{{ $item['deskripsi'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="card" style="padding:0;overflow-x:auto;">
        @if ($rows->isEmpty())
            <div class="empty">
                <div class="big">&#128269;</div>
                Tidak ada keluarga pada filter ini.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Kepala Keluarga</th>
                        <th>IKS</th>
                        <th style="min-width:160px;">Skor Prioritas</th>
                        <th>Segmen</th>
                        <th>Alasan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php
                            $family = $row['family'];
                            $analisis = $row['analisis'];
                            $iks = $family->iks();
                            $levelClass = $analisis['priority_level'];
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $family->kepala_keluarga ?: '(tanpa nama)' }}</strong>
                                <br><span style="color:var(--ink-soft);font-size:12px;">{{ $family->alamatLengkap() ?: '—' }}</span>
                                @if ($analisis['requires_human_review'])
                                    <br><span class="seg-flag">butuh tinjauan</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $iks['class'] }}">{{ $iks['label'] }}@if ($iks['score'] !== null) &middot; {{ round($iks['score'] * 100).'%' }}@endif</span>
                            </td>
                            <td>
                                <div class="score-row">
                                    <div class="bar-track" style="flex:1;min-width:90px;">
                                        <div class="bar-fill seg-fill {{ $levelClass }}" style="width:{{ $analisis['priority_score'] }}%"></div>
                                    </div>
                                    <span class="lvl {{ $levelClass }}">{{ $analisis['priority_score'] }} &middot; {{ $analisis['priority_level_label'] }}</span>
                                </div>
                            </td>
                            <td>
                                @forelse ($analisis['segments'] as $id)
                                    <span class="seg-chip">{{ $katalog[$id]['label'] }}</span>
                                @empty
                                    <span style="color:var(--ink-soft);font-size:12px;">—</span>
                                @endforelse
                            </td>
                            <td>
                                <ul class="seg-reasons">
                                    @foreach (array_slice($analisis['reasons'], 0, 3) as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                                @if (count($analisis['reasons']) > 3)
                                    <span style="color:var(--ink-soft);font-size:11.5px;">+ {{ count($analisis['reasons']) - 3 }} alasan lain</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('families.edit', $family) }}" class="btn ghost small">Tinjau</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="table-footer" style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-top:1px solid var(--line);">
                <span style="color:var(--ink-soft);font-size:12.5px;">
                    Menampilkan {{ $rows->firstItem() }}&ndash;{{ $rows->lastItem() }} dari {{ $rows->total() }} keluarga.
                </span>
                <div>{{ $rows->links() }}</div>
            </div>
        @endif
    </div>

    @if ($rekap['wilayah'] !== [])
        <div class="card" style="padding:0;overflow-x:auto;margin-top:16px;">
            <div style="padding:16px 16px 0 16px;">
                <h3 style="margin:0;">Sebaran Segmen per Wilayah</h3>
                <p style="color:var(--ink-soft);font-size:13px;margin:6px 0 12px 0;">Jumlah keluarga dan segmen dominan pada setiap kelurahan dalam wilayah terpilih.</p>
            </div>
            <table>
                <thead>
                    <tr><th>Kelurahan</th><th>Jml KK</th><th>Prioritas &Oslash;</th><th>Perlu Tinjauan</th><th>Segmen Terbanyak</th></tr>
                </thead>
                <tbody>
                    @foreach ($rekap['wilayah'] as $wil)
                        @php
                            $sorted = collect($wil['seg'])->sortDesc()->take(3);
                            $total = max(1, array_sum($wil['seg']));
                        @endphp
                        <tr>
                            <td><strong>{{ $wil['label'] }}</strong></td>
                            <td>{{ $wil['n'] }}</td>
                            <td><span class="lvl {{ \App\Support\Segmentasi::level($wil['avg_priority']) }}">{{ $wil['avg_priority'] }}</span></td>
                            <td>{{ $wil['perlu_tinjau'] }}</td>
                            <td>
                                @foreach ($sorted as $id => $count)
                                    <span class="seg-chip">{{ $katalog[$id]['label'] }} &middot; {{ $count }} ({{ round($count / $total * 100) }}%)</span>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card" style="margin-top:16px;">
        <h3 style="margin:0;">Cara membaca segmentasi</h3>
        <ul style="margin:10px 0 0 0;padding-left:18px;color:var(--ink-soft);font-size:13px;line-height:1.7;">
            <li><strong>Skor prioritas</strong> (0&ndash;100) = bobot masalah kesehatan + kebutuhan ibu &amp; anak + sanitasi + JKN + kualitas data + urgensi IKS. Urutan daftar disusun dari skor tertinggi.</li>
            <li><strong>Segmentasi multi-label</strong>: satu keluarga dapat masuk beberapa segmen sekaligus &mdash; kebutuhan berbeda tidak saling menggantikan.</li>
            <li><strong>Data kosong &ne; tidak sehat</strong>: indikator belum terisi dikeluarkan dari hitungan IKS dan hanya memicu segmen <em>data_perlu_verifikasi</em>, bukan status tidak sehat.</li>
            <li><strong>Bukan diagnosis medis</strong>: segmen hanya menentukan prioritas administrasi, edukasi, pemantauan, atau verifikasi &mdash; keputusan klinis tetap di tangan tenaga kesehatan.</li>
            <li>Segmen <em>sehat_stabil</em> dan <em>data_lengkap</em> hanya muncul bila seluruh data relatif lengkap dan tidak ada masalah prioritas.</li>
        </ul>
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
        })();
    </script>
@endsection