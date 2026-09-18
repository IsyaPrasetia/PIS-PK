@extends('layouts.app')

@php
    $memberList = old('members', $members);
    $action = $isEdit ? route('families.update', $family) : route('families.store');
@endphp

@section('page-title', $isEdit ? 'Ubah Data Keluarga' : 'Tambah Manual')
@section('title', $isEdit ? 'Ubah Data Keluarga' : 'Tambah / Ubah Data Keluarga')
@section('subtitle', 'Isi data anggota keluarga dan capaian 12 indikator.')

@section('tabs')
    @include('partials.input-tabs', ['active' => 'form'])
@endsection

@section('content')
    @if ($fromAi)
        <div class="card" style="margin-bottom:16px;">
            <h3>Hasil ekstraksi AI — silakan periksa</h3>
            <p style="color:var(--ink-soft);font-size:13px;">
                Bagian yang ditandai <span class="ai-flag">perlu dicek</span> tidak disebutkan jelas di catatan Anda.
                Koreksi bila perlu lalu simpan.
            </p>
        </div>
    @endif

    <form id="family-form" method="POST" action="{{ $action }}">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        @if ($fromAi)
            <input type="hidden" name="ai_note" value="{{ $aiNote ?? '' }}">
            <input type="hidden" name="ai_draft" value="{{ e(json_encode($aiDraft ?? [])) }}">
        @endif

        <div class="card">
            <div class="section-title">Data Keluarga</div>
            <div class="field-row">
                <div class="field">
                    <label>Nama Kepala Keluarga</label>
                    <input type="text" name="kepala_keluarga" value="{{ old('kepala_keluarga', $family->kepala_keluarga) }}" placeholder="mis. Sutrisno">
                    @error('kepala_keluarga')<span style="color:var(--clay);font-size:12px;">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label>Nomor Kartu Keluarga (KK)</label>
                    <input type="text" name="no_kk" value="{{ old('no_kk', $family->no_kk) }}" placeholder="mis. 3204xxxxxxxxxxxx">
                </div>
            </div>
            <div class="field">
                <label>Alamat Jalan / Dusun</label>
                <input type="text" name="jalan" value="{{ old('jalan', $family->jalan) }}" placeholder="mis. Jl. Anggrek No. 12">
            </div>
            <div class="field-row3">
                <div class="field"><label>RT</label><input type="text" name="rt" value="{{ old('rt', $family->rt) }}"></div>
                <div class="field"><label>RW</label><input type="text" name="rw" value="{{ old('rw', $family->rw) }}"></div>
                <div class="field"><label>Desa/Kelurahan</label><input type="text" name="desa" value="{{ old('desa', $family->desa) }}"></div>
            </div>
            <div class="field">
                <label>Kecamatan</label>
                <input type="text" name="kecamatan" value="{{ old('kecamatan', $family->kecamatan) }}">
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <div class="section-title">Anggota Keluarga</div>
            <div id="members-wrap">
                @foreach ($memberList as $index => $member)
                    <div class="member-row" data-member-row>
                        <div class="field" style="margin-bottom:0;">
                            <label>{{ $loop->first ? 'Nama' : '' }}</label>
                            <input type="text" name="members[{{ $index }}][nama]" value="{{ $member['nama'] ?? '' }}" placeholder="Nama anggota">
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label>{{ $loop->first ? 'Umur' : '' }}</label>
                            <input type="number" min="0" name="members[{{ $index }}][umur]" value="{{ $member['umur'] ?? '' }}">
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label>{{ $loop->first ? 'Jenis Kelamin' : '' }}</label>
                            <select name="members[{{ $index }}][jenis_kelamin]">
                                <option value="L" @selected(($member['jenis_kelamin'] ?? 'L') === 'L')>Laki-laki</option>
                                <option value="P" @selected(($member['jenis_kelamin'] ?? 'L') === 'P')>Perempuan</option>
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label>{{ $loop->first ? 'Hubungan' : '' }}</label>
                            <input type="text" name="members[{{ $index }}][hubungan]" value="{{ $member['hubungan'] ?? '' }}" placeholder="mis. Anak">
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label>{{ $loop->first ? 'NIK' : '' }}</label>
                            <input type="text" name="members[{{ $index }}][nik]" value="{{ $member['nik'] ?? '' }}">
                        </div>
                        <button type="button" class="btn danger small" data-remove-member style="height:38px;">&times;</button>
                    </div>
                @endforeach
            </div>
            @error('members')<span style="color:var(--clay);font-size:12px;">{{ $message }}</span>@enderror
            <button type="button" class="btn ghost small" id="add-member">+ Tambah Anggota</button>
        </div>

        <div class="card" style="margin-top:16px;">
            <div class="section-title">12 Indikator Keluarga Sehat</div>
            @foreach (\App\Support\Indikator::all() as $indicator)
                @php
                    $id = $indicator['id'];
                    $value = old('ind_'.$id, $indicatorValues[$id] ?? 'N');
                    $checked = $value === 'Y' || $value === 'T' || $value === 'N' ? $value : '';
                @endphp
                <div class="indicator-item">
                    <div class="domain">{{ $indicator['domain'] }}</div>
                    <div class="q">
                        {{ $indicator['q'] }}
                        @if (! empty($flagged[$id]))<span class="ai-flag">perlu dicek</span>@endif
                        @if ($value === '?')<span class="ai-missing" title="Pilih Ya, Tidak, atau Tidak berlaku">belum dijawab</span>@endif
                    </div>
                    <div class="yn">
                        <label class="{{ $checked === 'Y' ? 'sel-y' : '' }}"><input type="radio" name="ind_{{ $id }}" value="Y" @checked($checked === 'Y')> Ya</label>
                        <label class="{{ $checked === 'T' ? 'sel-t' : '' }}"><input type="radio" name="ind_{{ $id }}" value="T" @checked($checked === 'T')> Tidak</label>
                        <label class="{{ $checked === 'N' ? 'sel-n' : '' }}"><input type="radio" name="ind_{{ $id }}" value="N" @checked($checked === 'N')> Tidak berlaku</label>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card" style="margin-top:16px;">
            <div class="section-title">Catatan &amp; Petugas</div>
            <div class="field-row">
                <div class="field"><label>Nama Surveyor / Kader</label><input type="text" name="surveyor" value="{{ old('surveyor', $family->surveyor) }}"></div>
                <div class="field"><label>Tanggal Kunjungan</label><input type="date" name="tanggal" value="{{ old('tanggal', $family->tanggal?->format('Y-m-d')) }}"></div>
            </div>
            <div class="field"><label>Catatan Tambahan</label><textarea name="catatan">{{ old('catatan', $family->catatan) }}</textarea></div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data Keluarga' }}</button>
            <a href="{{ route('families.index') }}" class="btn ghost">Batal</a>
        </div>
    </form>

    <template id="member-template">
        <div class="member-row" data-member-row>
            <div class="field" style="margin-bottom:0;"><label></label><input type="text" name="members[__INDEX__][nama]" placeholder="Nama anggota"></div>
            <div class="field" style="margin-bottom:0;"><label></label><input type="number" min="0" name="members[__INDEX__][umur]"></div>
            <div class="field" style="margin-bottom:0;"><label></label>
                <select name="members[__INDEX__][jenis_kelamin]">
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;"><label></label><input type="text" name="members[__INDEX__][hubungan]" placeholder="mis. Anak"></div>
            <div class="field" style="margin-bottom:0;"><label></label><input type="text" name="members[__INDEX__][nik]"></div>
            <button type="button" class="btn danger small" data-remove-member style="height:38px;">&times;</button>
        </div>
    </template>
@endsection
