@extends('layouts.app')

@section('page-title', 'Manajemen Pengguna')
@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola akun admin wilayah, admin RW/RT, dan petugas lapangan.')

@section('topbar-action')
    <a href="{{ route('users.create') }}" class="btn">+ Tambah Pengguna</a>
@endsection

@section('content')
    <form method="GET" action="{{ route('users.index') }}" class="search-row" id="search-form">
        <div style="max-width:320px;flex:1;">
            <input type="search" name="q" id="search-input" placeholder="Cari nama, email, atau kecamatan..." value="{{ $term }}">
        </div>
    </form>

    <div class="card" style="padding:0;overflow-x:auto;">
        @if ($users->isEmpty())
            <div class="empty">
                <div class="big">&#128101;</div>
                {{ $term !== '' ? 'Tidak ada pengguna yang cocok.' : 'Belum ada pengguna.' }}
            </div>
        @else
            <table>
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Wilayah</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if ($user->created_by && $user->creator)
                                    <br><span style="color:var(--ink-soft);font-size:12px;">dibuat oleh {{ $user->creator->name }}</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge pra">{{ $user->role()->label() }}</span></td>
                            <td>{{ $user->scopeLabel() }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('users.edit', $user) }}" class="btn ghost small">Ubah</a>
                                    @if ($user->id !== auth()->id())
                                        <button type="button" class="btn danger small"
                                            data-delete-url="{{ route('users.destroy', $user) }}"
                                            data-delete-name="{{ $user->name }}">Hapus</button>
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