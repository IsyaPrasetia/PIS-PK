@extends('layouts.app')

@section('page-title', 'Rekap IKS')
@section('title', 'Rekap IKS per Wilayah')
@section('subtitle', 'Perhitungan Indeks Keluarga Sehat otomatis, berjenjang dari Desa, RW, RT, hingga per Kartu Keluarga.')

@section('content')
    @if (\App\Models\Family::count() === 0)
        <div class="empty card">
            <div class="big">&#128202;</div>
            <h3>Belum ada data untuk direkap</h3>
            <p>Tambahkan data keluarga terlebih dahulu agar rekap IKS per Desa, RW, RT, dan KK dapat dihitung otomatis.</p>
            <div style="margin-top:14px;display:flex;gap:10px;justify-content:center;">
                <a href="{{ route('ai.index') }}" class="btn">Input dengan AI</a>
                <a href="{{ route('families.create') }}" class="btn ghost">Tambah Manual</a>
            </div>
        </div>
    @else
        <div class="grid-cards">
            <div class="stat-card"><div class="num">{{ $overall['n'] }}</div><div class="lbl">Total KK {{ $filterDesa !== '' ? 'di Desa '.$filterDesa : 'Terdata' }}</div></div>
            <div class="stat-card"><div class="num">{{ $overall['avg'] !== null ? round($overall['avg'] * 100).'%' : '—' }}</div><div class="lbl">Rata-rata Indeks Keluarga Sehat (IKS)</div></div>
            <div class="stat-card"><div class="num" style="color:var(--success)">{{ $overall['sehat'] }}</div><div class="lbl">Keluarga Sehat (IKS &gt; 0.8)</div></div>
            <div class="stat-card"><div class="num" style="color:var(--amber)">{{ $overall['pra'] }}</div><div class="lbl">Pra-Sehat (0.5&ndash;0.8)</div></div>
            <div class="stat-card"><div class="num" style="color:var(--clay)">{{ $overall['tidak'] }}</div><div class="lbl">Tidak Sehat (&lt; 0.5)</div></div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <form method="GET" action="{{ route('rekap.index') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:12.5px;color:var(--ink-soft);font-weight:600;">Filter Desa</span>
                    <select name="desa" style="width:auto;min-width:180px;" onchange="this.form.submit()">
                        <option value="">Semua Desa</option>
                        @foreach ($desaOptions as $option)
                            <option value="{{ $option }}" @selected($filterDesa === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn ghost small" data-rekap-expand-all>Perluas Semua</button>
                    <button type="button" class="btn ghost small" data-rekap-collapse-all>Ciutkan Semua</button>
                </div>
            </div>
            <p style="color:var(--ink-soft);font-size:12.5px;margin:10px 0 0 0;">
                IKS dihitung otomatis dari data 12 indikator setiap keluarga (Ya &divide; (Ya+Tidak)). Klik nama
                <strong>Desa</strong> untuk melihat rincian per <strong>RW</strong>, lalu per <strong>RT</strong>, hingga per
                <strong>Kartu Keluarga (KK)</strong>.
            </p>
        </div>

        <div class="card" style="padding:0;overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>Wilayah</th><th>Jml KK</th><th>Rata-rata IKS</th><th>Sehat</th><th>Pra-Sehat</th><th>Tidak Sehat</th></tr>
                </thead>
                <tbody>
                    @forelse ($tree as $desa => $rwMap)
                        @php
                            $desaKey = 'd:'.$desa;
                            $desaFamilies = collect($rwMap)->flatMap(fn ($rtMap) => collect($rtMap)->flatten(1));
                            $desaStats = \App\Models\Family::iksStatsFor($desaFamilies);
                        @endphp
                        <tr data-rekap-toggle="{{ $desaKey }}" style="cursor:pointer;">
                            <td style="padding-left:14px;font-weight:700;"><span class="rekap-arrow" style="display:inline-block;width:14px;color:var(--ink-soft);">&#9656;</span>{{ $desa }}</td>
                            <td>{{ $desaStats['n'] }}</td>
                            <td><span class="badge {{ $desaStats['avg'] === null ? 'na' : ($desaStats['avg'] > 0.8 ? 'sehat' : ($desaStats['avg'] >= 0.5 ? 'pra' : 'tidak')) }}">{{ $desaStats['avg'] === null ? '—' : round($desaStats['avg'] * 100).'%' }}</span></td>
                            <td>{{ $desaStats['sehat'] }}</td>
                            <td>{{ $desaStats['pra'] }}</td>
                            <td>{{ $desaStats['tidak'] }}</td>
                        </tr>
                        <tr class="rekap-children is-hidden" data-rekap-children="{{ $desaKey }}">
                            <td colspan="6" style="padding:0;border-bottom:none;background:#f7faf7;">
                                <table style="margin:0;">
                                    @foreach ($rwMap as $rw => $rtMap)
                                        @php
                                            $rwKey = $desaKey.'>rw:'.$rw;
                                            $rwFamilies = collect($rtMap)->flatten(1);
                                            $rwStats = \App\Models\Family::iksStatsFor($rwFamilies);
                                        @endphp
                                        <tr data-rekap-toggle="{{ $rwKey }}" style="cursor:pointer;">
                                            <td style="padding-left:34px;font-weight:500;"><span class="rekap-arrow" style="display:inline-block;width:14px;color:var(--ink-soft);">&#9656;</span>RW {{ $rw }}</td>
                                            <td>{{ $rwStats['n'] }}</td>
                                            <td><span class="badge {{ $rwStats['avg'] === null ? 'na' : ($rwStats['avg'] > 0.8 ? 'sehat' : ($rwStats['avg'] >= 0.5 ? 'pra' : 'tidak')) }}">{{ $rwStats['avg'] === null ? '—' : round($rwStats['avg'] * 100).'%' }}</span></td>
                                            <td>{{ $rwStats['sehat'] }}</td>
                                            <td>{{ $rwStats['pra'] }}</td>
                                            <td>{{ $rwStats['tidak'] }}</td>
                                        </tr>
                                        <tr class="rekap-children is-hidden" data-rekap-children="{{ $rwKey }}">
                                            <td colspan="6" style="padding:0;border-bottom:none;background:#f7faf7;">
                                                <table style="margin:0;">
                                                    @foreach ($rtMap as $rt => $rtFamilies)
                                                        @php
                                                            $rtKey = $rwKey.'>rt:'.$rt;
                                                            $rtStats = \App\Models\Family::iksStatsFor($rtFamilies);
                                                        @endphp
                                                        <tr data-rekap-toggle="{{ $rtKey }}" style="cursor:pointer;">
                                                            <td style="padding-left:54px;"><span class="rekap-arrow" style="display:inline-block;width:14px;color:var(--ink-soft);">&#9656;</span>RT {{ $rt }}</td>
                                                            <td>{{ $rtStats['n'] }}</td>
                                                            <td><span class="badge {{ $rtStats['avg'] === null ? 'na' : ($rtStats['avg'] > 0.8 ? 'sehat' : ($rtStats['avg'] >= 0.5 ? 'pra' : 'tidak')) }}">{{ $rtStats['avg'] === null ? '—' : round($rtStats['avg'] * 100).'%' }}</span></td>
                                                            <td>{{ $rtStats['sehat'] }}</td>
                                                            <td>{{ $rtStats['pra'] }}</td>
                                                            <td>{{ $rtStats['tidak'] }}</td>
                                                        </tr>
                                                        <tr class="rekap-children is-hidden" data-rekap-children="{{ $rtKey }}">
                                                            <td colspan="6" style="padding:0;border-bottom:none;background:#f7faf7;">
                                                                <table style="margin:0;">
                                                                    @foreach ($rtFamilies as $family)
                                                                        @php $iks = $family->iks(); @endphp
                                                                        <tr>
                                                                            <td style="padding-left:74px;">
                                                                                {{ $family->kepala_keluarga ?: '(tanpa nama)' }}
                                                                                @if ($family->no_kk)<span style="color:var(--ink-soft);font-size:11.5px;">&middot; KK: {{ $family->no_kk }}</span>@endif
                                                                            </td>
                                                                            <td>1</td>
                                                                            <td><span class="badge {{ $iks['class'] }}">{{ $iks['score'] !== null ? round($iks['score'] * 100).'%' : '—' }}</span></td>
                                                                            <td>{{ $iks['class'] === 'sehat' ? 1 : 0 }}</td>
                                                                            <td>{{ $iks['class'] === 'pra' ? 1 : 0 }}</td>
                                                                            <td>{{ $iks['class'] === 'tidak' ? 1 : 0 }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;color:var(--ink-soft);padding:24px;">Tidak ada data untuk wilayah ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
