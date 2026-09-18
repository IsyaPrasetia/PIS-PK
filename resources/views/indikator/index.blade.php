@extends('layouts.app')

@section('page-title', '12 Indikator')
@section('title', '12 Indikator Keluarga Sehat')
@section('subtitle', 'Rujukan indikator Program Indonesia Sehat dengan Pendekatan Keluarga.')

@section('content')
    @foreach ($domains as $domain => $items)
        <div class="card" style="margin-bottom:14px;">
            <h3>{{ $domain }}</h3>
            <table>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td style="width:36px;color:var(--ink-soft);">{{ \App\Support\Indikator::number($item['id']) }}</td>
                            <td>{{ $item['q'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="card">
        <h3>Cara menghitung Indeks Keluarga Sehat (IKS)</h3>
        <p style="color:var(--ink-soft);">IKS = (jumlah indikator "Ya") &divide; (jumlah indikator "Ya" + jumlah indikator "Tidak"). Indikator yang tidak berlaku bagi keluarga tersebut dikeluarkan dari perhitungan.</p>
        <p style="color:var(--ink-soft);">
            <span class="badge sehat">Keluarga Sehat</span> IKS &gt; 0.80 &nbsp;
            <span class="badge pra">Pra-Sehat</span> 0.50 &ndash; 0.80 &nbsp;
            <span class="badge tidak">Tidak Sehat</span> IKS &lt; 0.50
        </p>
    </div>
@endsection
