@extends('layouts.app')

@section('page-title', $isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna')
@section('title', $isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna')
@section('subtitle', $isEdit ? 'Perbarui data akun pengguna.' : 'Buat akun baru untuk admin wilayah, admin RW/RT, atau petugas.')

@section('content')
    <div class="card" style="max-width:760px;">
        <form method="POST" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}" id="user-form">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            @if (($isEdit ? old('kecamatan', $user->kecamatan) : old('kecamatan', auth()->user()->kecamatan)) && !auth()->user()->isSuperadmin())
                <div class="ai-box">
                    <h3>Wilayah Anda</h3>
                    <p>Akun ini berada di kecamatan <strong>{{ $isEdit ? $user->kecamatan : auth()->user()->kecamatan }}</strong> sesuai scope admin wilayah Anda.</p>
                </div>
            @endif

            <div class="section-title">Akun</div>
            <div class="field">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="field">
                    <label for="password">{{ $isEdit ? 'Kata Sandi (kosongkan jika tidak diganti)' : 'Kata Sandi' }}</label>
                    <input type="password" id="password" name="password" {!! $isEdit ? '' : 'required' !!} autocomplete="new-password">
                </div>
            </div>

            <div class="section-title">Peran &amp; Wilayah</div>
            <div class="field">
                <label for="role">Peran</label>
                <select id="role" name="role" required>
                    @foreach (\App\Enums\UserRole::options() as $key => $role)
                        @if (in_array($key, $roles, true))
                            <option value="{{ $key }}" @selected(old('role', $user->role->value ?? $user->role) === $key)>
                                {{ $role->label() }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="field" id="scope-kecamatan">
                <label for="kecamatan">Kecamatan</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select id="kecamatan" name="kecamatan" style="flex:1;">
                        <option value="">— Pilih Kecamatan —</option>
                        @foreach (array_keys($wilayah) as $kecamatan)
                            <option value="{{ $kecamatan }}" @selected(old('kecamatan', $user->kecamatan) === $kecamatan)>{{ $kecamatan }}</option>
                        @endforeach
                    </select>
                    @if (auth()->user()->isSuperadmin())
                        <button type="button" class="btn ghost small" data-wilayah-add="kecamatan" title="Tambah kecamatan baru">+ Tambah</button>
                    @endif
                </div>
            </div>
            <div class="field" id="scope-desa">
                <label for="desa">Desa/Kelurahan</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select id="desa" name="desa" style="flex:1;">
                        <option value="">— Pilih Desa/Kelurahan —</option>
                    </select>
                    <button type="button" class="btn ghost small" data-wilayah-add="desa" title="Tambah desa/kelurahan baru">+ Tambah</button>
                </div>
            </div>
            <div class="field" id="scope-rw">
                <label for="rw">RW</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select id="rw" name="rw" style="flex:1;">
                        <option value="">— Pilih RW —</option>
                    </select>
                    <button type="button" class="btn ghost small" data-wilayah-add="rw" title="Tambah RW baru">+ Tambah</button>
                </div>
            </div>
            <div class="field" id="scope-rt">
                <label for="rt">RT</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select id="rt" name="rt" style="flex:1;">
                        <option value="">— Pilih RT —</option>
                    </select>
                    <button type="button" class="btn ghost small" data-wilayah-add="rt" title="Tambah RT baru">+ Tambah</button>
                </div>
            </div>

            <p style="color:var(--ink-soft);font-size:12.5px;" id="scope-help"></p>

            <div class="form-actions">
                <button type="submit" class="btn">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
                <a href="{{ route('users.index') }}" class="btn ghost">Batal</a>
            </div>
        </form>
    </div>

    <div class="modal-backdrop is-hidden" id="wilayah-modal">
        <div class="modal">
            <h3 id="wilayah-modal-title">Tambah Wilayah</h3>
            <p id="wilayah-modal-context" style="color:var(--ink-soft);font-size:13px;"></p>
            <div class="field">
                <label for="wilayah-modal-input" id="wilayah-modal-label">Nama</label>
                <input type="text" id="wilayah-modal-input" autocomplete="off">
            </div>
            <div class="actions">
                <button type="button" class="btn ghost" data-wilayah-cancel>Batal</button>
                <button type="button" class="btn" data-wilayah-save>Simpan</button>
            </div>
        </div>
    </div>

    <script type="application/json" id="wilayah-tree-data">@json($wilayah)</script>

    <script>
        (function () {
            var tree = {};
            try {
                tree = JSON.parse(document.getElementById('wilayah-tree-data').textContent);
            } catch (e) {}

            var hintText = {
                'superadmin': 'Superadmin mengelola seluruh wilayah dan semua akun.',
                'admin_wilayah': 'Admin wilayah mengelola seluruh data di kecamatan yang dipilih.',
                'admin_rw': 'Admin RW mengelola data di kecamatan, desa, dan RW yang dipilih.',
                'admin_rt': 'Admin RT mengelola data di kecamatan, desa, RW, dan RT yang dipilih.',
                'petugas': 'Petugas hanya bisa input data baru; perubahan data lama harus lewat admin RT/RW.'
            };

            var labels = { desa: 'Desa/Kelurahan', rw: 'RW', rt: 'RT' };
            var help = document.getElementById('scope-help');
            var roleSel = document.getElementById('role');
            var isSupervisor = {{ auth()->user()->isSuperadmin() ? 'true' : 'false' }};
            var managerKecamatan = @json(auth()->user()->kecamatan ?? '');
            var kecSel = document.getElementById('kecamatan');
            var desaSel = document.getElementById('desa');
            var rwSel = document.getElementById('rw');
            var rtSel = document.getElementById('rt');

            var initial = {
                desa: @json(old('desa', $user->desa)),
                rw: @json(old('rw', $user->rw)),
                rt: @json(old('rt', $user->rt))
            };

            function placeholder(select) {
                return '<option value="">— Pilih ' + labels[select.id] + ' —</option>';
            }

            function empty(select) {
                select.innerHTML = placeholder(select);
            }

            function fill(select, values, selected) {
                select.innerHTML = placeholder(select) + values.map(function (v) {
                    return '<option value="' + v + '"' + (String(v) === String(selected || '') ? ' selected' : '') + '>' + v + '</option>';
                }).join('');
            }

            function optionsFor(select) {
                var kec = kecSel.value;
                var desa = desaSel.value;
                var rw = rwSel.value;

                if (select === desaSel) return kec && tree[kec] ? Object.keys(tree[kec]) : [];
                if (select === rwSel) return kec && desa && tree[kec][desa] ? Object.keys(tree[kec][desa]) : [];
                if (select === rtSel) return kec && desa && rw && tree[kec][desa][rw] ? tree[kec][desa][rw] : [];
                return [];
            }

            function repopulate(from) {
                var chain = [desaSel, rwSel, rtSel].filter(function (s) {
                    return s !== from;
                });
                chain.forEach(empty);

                if (from === kecSel || from === roleSel) {
                    fill(desaSel, optionsFor(desaSel), initial.desa);
                    if (desaSel.value) fill(rwSel, optionsFor(rwSel), initial.rw);
                    if (rwSel.value) fill(rtSel, optionsFor(rtSel), initial.rt);
                    return;
                }

                if (from === desaSel) {
                    fill(rwSel, optionsFor(rwSel), initial.rw);
                    if (rwSel.value) fill(rtSel, optionsFor(rtSel), initial.rt);
                    return;
                }

                if (from === rwSel) {
                    fill(rtSel, optionsFor(rtSel), initial.rt);
                }
            }

            kecSel.addEventListener('change', function () {
                if (!isSupervisor && kecSel.value !== managerKecamatan) {
                    kecSel.value = managerKecamatan;
                }
                repopulate(kecSel);
            });

            desaSel.addEventListener('change', function () { repopulate(desaSel); });
            rwSel.addEventListener('change', function () { repopulate(rwSel); });

            roleSel.addEventListener('change', function () {
                help.textContent = hintText[roleSel.value] || '';
                toggleScope();
                repopulate(roleSel);
            });

            function toggleScope() {
                var role = roleSel.value;
                var isSuper = role === 'superadmin';
                var isWilayah = role === 'admin_wilayah';
                var isRw = role === 'admin_rw';

                document.getElementById('scope-kecamatan').style.display = isSuper ? 'none' : 'block';
                document.getElementById('scope-desa').style.display = isSuper || isWilayah ? 'none' : 'block';
                document.getElementById('scope-rw').style.display = isSuper || isWilayah || isRw ? 'none' : 'block';
                document.getElementById('scope-rt').style.display = isSuper || isWilayah || isRw ? 'none' : 'block';

                if (!isSupervisor) kecSel.value = managerKecamatan;
                if (!isSupervisor && kecSel.value === managerKecamatan) repopulate(kecSel);
            }

            toggleScope();
            help.textContent = hintText[roleSel.value] || '';
            repopulate(kecSel);

            var addLabels = {
                kecamatan: 'Nama kecamatan baru',
                desa: 'Nama desa/kelurahan baru',
                rw: 'Nomor RW baru',
                rt: 'Nomor RT baru'
            };

            function addOption(select, value) {
                if (!Array.prototype.some.call(select.options, function (o) { return o.value === value; })) {
                    var opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = value;
                    select.appendChild(opt);
                }
            }

            var modal = document.getElementById('wilayah-modal');
            var modalTitle = document.getElementById('wilayah-modal-title');
            var modalContext = document.getElementById('wilayah-modal-context');
            var modalLabel = document.getElementById('wilayah-modal-label');
            var modalInput = document.getElementById('wilayah-modal-input');
            var pending = null;

            var levelTitles = {
                kecamatan: 'Tambah Kecamatan',
                desa: 'Tambah Desa/Kelurahan',
                rw: 'Tambah RW',
                rt: 'Tambah RT'
            };

            function closeModal() {
                modal.classList.add('is-hidden');
                pending = null;
            }

            function openModal(level, kec, desa, rw) {
                pending = { level: level, kec: kec, desa: desa, rw: rw };
                modalTitle.textContent = levelTitles[level] || 'Tambah Wilayah';
                modalLabel.textContent = addLabels[level] || 'Nama';
                modalInput.value = '';

                var context = [];
                if (level !== 'kecamatan' && kec) context.push(kec);
                if ((level === 'rw' || level === 'rt') && desa) context.push(desa);
                if (level === 'rt' && rw) context.push('RW ' + rw);
                modalContext.textContent = context.length ? 'Di bawah: ' + context.join(' / ') : '';
                modalContext.style.display = context.length ? 'block' : 'none';

                modal.classList.remove('is-hidden');
                modalInput.focus();
            }

            function saveWilayah() {
                if (!pending) return;

                var name = modalInput.value.trim();
                if (name === '') {
                    modalInput.focus();
                    return;
                }

                var saveBtn = modal.querySelector('[data-wilayah-save]');
                saveBtn.disabled = true;

                fetch(@json(route('wilayah.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        level: pending.level,
                        kecamatan: pending.kec,
                        desa: pending.desa,
                        rw: pending.rw,
                        nama: name
                    })
                }).then(function (response) {
                    if (!response.ok) throw new Error('gagal');
                    return response.json();
                }).then(function (result) {
                    var level = pending.level;
                    var kec = pending.kec;
                    var desa = pending.desa;
                    var rw = pending.rw;

                    if (level === 'kecamatan') {
                        tree[name] = tree[name] || {};
                        addOption(kecSel, name);
                        kecSel.value = name;
                        repopulate(kecSel);
                    } else if (level === 'desa') {
                        tree[kec] = tree[kec] || {};
                        tree[kec][name] = tree[kec][name] || {};
                        addOption(desaSel, name);
                        desaSel.value = name;
                        repopulate(desaSel);
                    } else if (level === 'rw') {
                        tree[kec][desa] = tree[kec][desa] || {};
                        tree[kec][desa][name] = tree[kec][desa][name] || [];
                        addOption(rwSel, name);
                        rwSel.value = name;
                        repopulate(rwSel);
                    } else {
                        tree[kec][desa][rw] = tree[kec][desa][rw] || [];
                        if (tree[kec][desa][rw].indexOf(name) === -1) tree[kec][desa][rw].push(name);
                        addOption(rtSel, name);
                        rtSel.value = name;
                    }

                    closeModal();
                    if (window.showToast) window.showToast(result.message);
                }).catch(function () {
                    window.alert('Gagal menambah wilayah. Coba lagi.');
                }).finally(function () {
                    saveBtn.disabled = false;
                });
            }

            document.querySelectorAll('[data-wilayah-add]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var level = button.getAttribute('data-wilayah-add');
                    var kec = kecSel.value;
                    var desa = desaSel.value;
                    var rw = rwSel.value;

                    if (level !== 'kecamatan' && !kec) { window.alert('Pilih kecamatan terlebih dahulu.'); return; }
                    if ((level === 'rw' || level === 'rt') && !desa) { window.alert('Pilih desa/kelurahan terlebih dahulu.'); return; }
                    if (level === 'rt' && !rw) { window.alert('Pilih RW terlebih dahulu.'); return; }

                    openModal(level, kec, desa, rw);
                });
            });

            modal.querySelector('[data-wilayah-cancel]').addEventListener('click', closeModal);
            modal.querySelector('[data-wilayah-save]').addEventListener('click', saveWilayah);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });
            modalInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    saveWilayah();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('is-hidden')) closeModal();
            });
        })();
    </script>
@endsection