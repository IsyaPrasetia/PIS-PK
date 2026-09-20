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

    @endsection
