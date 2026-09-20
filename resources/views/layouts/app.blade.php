<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'SIPANDAI') — Pendataan Keluarga Sehat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $isPetugas = $user->role() === \App\Enums\UserRole::Petugas;
    $nav = [
        ['id' => 'dashboard', 'icon' => '&#9673;', 'label' => 'Dasbor', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'visible' => !$isPetugas],
        ['id' => 'list', 'icon' => '&#9776;', 'label' => 'Data Keluarga', 'route' => 'families.index', 'active' => request()->routeIs('families.*'), 'visible' => true],
        ['id' => 'rekap', 'icon' => '&#128202;', 'label' => 'Rekap IKS Wilayah', 'route' => 'rekap.index', 'active' => request()->routeIs('rekap.index'), 'visible' => !$isPetugas],
        ['id' => 'segmentasi', 'icon' => '&#127919;', 'label' => 'Segmentasi Keluarga', 'route' => 'segmentasi.index', 'active' => request()->routeIs('segmentasi.index'), 'visible' => !$isPetugas],
        ['id' => 'form', 'icon' => '&#9998;', 'label' => 'Input Keluarga', 'route' => 'families.create', 'active' => request()->routeIs('families.create'), 'visible' => true],
        ['id' => 'ai', 'icon' => '&#10022;', 'label' => 'Input dengan AI', 'route' => 'ai.index', 'active' => request()->routeIs('ai.index'), 'visible' => true],
        ['id' => 'excel', 'icon' => '&#128196;', 'label' => 'Impor dari Excel', 'route' => 'impor.index', 'active' => request()->routeIs('impor.index'), 'visible' => !$isPetugas],
        ['id' => 'indicators', 'icon' => '&#10003;', 'label' => '12 Indikator', 'route' => 'indikator.index', 'active' => request()->routeIs('indikator.index'), 'visible' => !$isPetugas],
        ['id' => 'users', 'icon' => '&#128101;', 'label' => 'Kelola Pengguna', 'route' => 'users.index', 'active' => request()->routeIs('users.*'), 'visible' => $user->canManageUsers()],
        ['id' => 'wilayah', 'icon' => '&#127968;', 'label' => 'Master Wilayah', 'route' => 'wilayah.index', 'active' => request()->routeIs('wilayah.*'), 'visible' => $user->canManageUsers()],
    ];
@endphp
<div id="app">
    <div class="sidebar">
        <div class="brand">
            <div class="mark">SIPANDAI</div>
            <div class="sub">Pendataan Keluarga Sehat<br>Pendekatan Keluarga</div>
        </div>
        <div class="sidebar-nav">
            @foreach ($nav as $item)
                @if ($item['visible'])
                    <a href="{{ route($item['route']) }}" class="nav-item {{ $item['active'] ? 'active' : '' }}">
                        <span class="nav-icon">{!! $item['icon'] !!}</span>{{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
        <div class="sidebar-foot">
            <div class="user-chip">
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-role">{{ $user->role()->label() }}</div>
                <div class="user-scope">{{ $user->scopeLabel() }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:10px;">
                @csrf
                <button type="submit" class="nav-item logout-btn">&#8617; Keluar</button>
            </form>
            <div class="sidebar-brand" style="text-align:center;font-size:11px;color:rgba(255,255,255,0.45);margin-top:14px;">
                &copy; Nexlipse 2026
            </div>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div>
                <h1>@yield('title')</h1>
                <p>@yield('subtitle')</p>
            </div>
            @yield('topbar-action')
        </div>

        @yield('tabs')

        @yield('content')
    </div>
</div>

<div class="modal-backdrop is-hidden" id="delete-modal">
    <div class="modal">
        <h3>Hapus data keluarga?</h3>
        <p>Data <strong id="delete-modal-name">ini</strong> akan dihapus permanen dan tidak dapat dikembalikan.</p>
        <div class="actions">
            <button type="button" class="btn ghost" data-delete-cancel>Batal</button>
            <form method="POST" id="delete-modal-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Ya, Hapus</button>
            </form>
        </div>
    </div>
</div>

@if (session('success') || session('error'))
    <div class="toast {{ session('error') ? 'error' : '' }}" id="toast">{{ session('success') ?? session('error') }}</div>
@endif
</body>
</html>