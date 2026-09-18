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
    <form method="GET" action="{{ route('families.index') }}" class="search-row" id="search-form">
        <div style="max-width:320px;flex:1;">
            <input type="search" name="q" id="search-input" placeholder="Cari nama, alamat, atau no. KK..." value="{{ $term }}">
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
        @endif
    </div>
@endsection
