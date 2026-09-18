import * as XLSX from './lib/xlsx.mjs';

/* ============================= UTIL ============================= */

function showToast(message, isError = false) {
    let toast = document.getElementById('toast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.classList.toggle('error', isError);
    toast.classList.remove('is-hidden');

    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => {
        toast.classList.add('is-hidden');
    }, 2600);
}

window.showToast = showToast;

/* ============================= TOAST FLASH ============================= */

(function () {
    const toast = document.getElementById('toast');
    if (toast) {
        window.__toastTimer = setTimeout(() => toast.classList.add('is-hidden'), 3000);
    }
})();

/* ============================= HAPUS (MODAL) ============================= */

(function () {
    const modal = document.getElementById('delete-modal');
    if (!modal) return;

    const form = document.getElementById('delete-modal-form');
    const nameEl = document.getElementById('delete-modal-name');

    document.querySelectorAll('[data-delete-url]').forEach((button) => {
        button.addEventListener('click', () => {
            form.setAttribute('action', button.getAttribute('data-delete-url'));
            nameEl.textContent = button.getAttribute('data-delete-name') || 'ini';
            modal.classList.remove('is-hidden');
        });
    });

    modal.querySelectorAll('[data-delete-cancel]').forEach((button) => {
        button.addEventListener('click', () => modal.classList.add('is-hidden'));
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.classList.add('is-hidden');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') modal.classList.add('is-hidden');
    });
})();

/* ============================= KONFIRMASI ============================= */

(function () {
    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm') || 'Lanjutkan?')) {
                event.preventDefault();
            }
        });
    });
})();

/* ============================= PENCARIAN ============================= */(function () {
    const form = document.getElementById('search-form');
    const input = document.getElementById('search-input');
    if (!form || !input) return;

    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 400);
    });
})();

/* ============================= REKAP TOGGLE ============================= */

(function () {
    const rows = document.querySelectorAll('[data-rekap-toggle]');
    if (!rows.length) return;

    const childFor = (key) => document.querySelector('[data-rekap-children=' + JSON.stringify(key) + ']');

    const setArrow = (row, expanded) => {
        const arrow = row.querySelector('.rekap-arrow');
        if (arrow) arrow.textContent = expanded ? '\u25BE' : '\u25B8';
    };

    rows.forEach((row) => {
        row.addEventListener('click', (event) => {
            event.stopPropagation();
            const key = row.getAttribute('data-rekap-toggle');
            const child = childFor(key);
            if (!child) return;

            const expanded = child.classList.toggle('is-hidden') === false;
            setArrow(row, expanded);
        });
    });

    document.querySelector('[data-rekap-expand-all]')?.addEventListener('click', () => {
        document.querySelectorAll('[data-rekap-children]').forEach((el) => el.classList.remove('is-hidden'));
        rows.forEach((row) => setArrow(row, true));
    });

    document.querySelector('[data-rekap-collapse-all]')?.addEventListener('click', () => {
        document.querySelectorAll('[data-rekap-children]').forEach((el) => el.classList.add('is-hidden'));
        rows.forEach((row) => setArrow(row, false));
    });
})();

/* ============================= FORM KELUARGA ============================= */

(function () {
    const wrap = document.getElementById('members-wrap');
    const template = document.getElementById('member-template');
    if (!wrap) return;

    document.getElementById('add-member')?.addEventListener('click', () => {
        if (!template) return;
        const index = 'n' + Date.now();
        wrap.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
    });

    wrap.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-member]');
        if (!button) return;
        if (wrap.querySelectorAll('[data-member-row]').length > 1) {
            button.closest('[data-member-row]').remove();
        }
    });

    document.querySelectorAll('.yn').forEach((group) => {
        group.addEventListener('change', () => {
            group.querySelectorAll('label').forEach((label) => {
                const input = label.querySelector('input');
                label.classList.toggle('sel-y', input.checked && input.value === 'Y');
                label.classList.toggle('sel-t', input.checked && input.value === 'T');
                label.classList.toggle('sel-n', input.checked && input.value === 'N');
            });
        });
    });
})();

/* ============================= IMPOR EXCEL ============================= */

(function () {
    const root = document.getElementById('impor-app');
    if (!root || !XLSX) return;

    const storeUrl = root.dataset.storeUrl;
    const indexUrl = root.dataset.indexUrl;
    const indicatorIds = (root.dataset.indicatorIds || '').split(',').filter(Boolean);

    const fileInput = document.getElementById('excel-file');
    const filenameEl = document.getElementById('excel-filename');
    const errorEl = document.getElementById('excel-error');
    const processBtn = document.getElementById('excel-process');
    const resultCard = document.getElementById('excel-result');
    const resultSummary = document.getElementById('excel-result-summary');
    const draftsEl = document.getElementById('excel-drafts');
    const saveBtn = document.getElementById('excel-save-all');
    const discardBtn = document.getElementById('excel-discard');

    const state = { rawRows: [], drafts: [] };

    const familyHeaders = {
        'nama kepala keluarga': 'kepala_keluarga',
        'nama kepala': 'kepala_keluarga',
        'kepala keluarga': 'kepala_keluarga',
        'no kk': 'no_kk',
        'nomor kk': 'no_kk',
        'no. kk': 'no_kk',
        'alamat jalan/dusun': 'jalan',
        'alamat jalan': 'jalan',
        'alamat': 'jalan',
        'jalan': 'jalan',
        'rt': 'rt',
        'rw': 'rw',
        'desa/kelurahan': 'desa',
        'desa': 'desa',
        'kelurahan': 'desa',
        'kecamatan': 'kecamatan',
        'surveyor': 'surveyor',
        'nama surveyor': 'surveyor',
        'tanggal kunjungan': 'tanggal',
        'tanggal': 'tanggal',
        'catatan': 'catatan',
    };

    const indicatorHeaders = {
        'kb': 'kb',
        'bersalin di faskes': 'bersalin',
        'persalinan di fasilitas kesehatan': 'bersalin',
        'imunisasi dasar lengkap': 'imunisasi',
        'asi eksklusif': 'asi',
        'balita dipantau': 'balita',
        'tb berobat standar': 'tb',
        'hipertensi berobat teratur': 'hipertensi',
        'gangguan jiwa diobati': 'jiwa',
        'tidak merokok': 'rokok',
        'anggota jkn': 'jkn',
        'akses air bersih': 'air',
        'jamban sehat': 'jamban',
    };

    const normalizeAnswer = (value) => {
        const text = String(value ?? '').trim().toLowerCase();
        if (text === '') return 'N';
        if (text.startsWith('tidak berlaku') || text === 'n/a' || text === 'na') return 'N';
        if (text.startsWith('tidak')) return 'T';
        if (text.startsWith('ya')) return 'Y';
        return 'N';
    };

    const excelDate = (value) => {
        if (typeof value === 'number') {
            try {
                return XLSX.SSF.format('yyyy-mm-dd', value);
            } catch (e) {
                return '';
            }
        }
        return String(value ?? '').trim();
    };

    const mapRow = (row) => {
        const family = { indikator: {}, anggota: [] };
        const members = {};

        const memberFor = (index) => {
            members[index] = members[index] || { nama: '', umur: '', jenis_kelamin: 'L', hubungan: '', nik: '' };
            return members[index];
        };

        Object.entries(row).forEach(([header, value]) => {
            const key = String(header).trim().toLowerCase();

            if (familyHeaders[key]) {
                family[familyHeaders[key]] = key === 'tanggal' ? excelDate(value) : String(value ?? '').trim();
                return;
            }

            if (indicatorHeaders[key]) {
                family.indikator[indicatorHeaders[key]] = normalizeAnswer(value);
                return;
            }

            let match;
            if ((match = key.match(/^nama anggota\s*(\d*)$/))) {
                memberFor(match[1] || '1').nama = String(value ?? '').trim();
            } else if ((match = key.match(/^umur\s*(\d*)$/))) {
                memberFor(match[1] || '1').umur = String(value ?? '').trim();
            } else if ((match = key.match(/^(?:jk|jenis kelamin)\s*(\d*)/))) {
                memberFor(match[1] || '1').jenis_kelamin = String(value ?? '').trim().toUpperCase().startsWith('P') ? 'P' : 'L';
            } else if ((match = key.match(/^hubungan\s*(\d*)$/))) {
                memberFor(match[1] || '1').hubungan = String(value ?? '').trim();
            } else if ((match = key.match(/^nik\s*(\d*)$/))) {
                memberFor(match[1] || '1').nik = String(value ?? '').trim();
            }
        });

        family.anggota = Object.keys(members)
            .sort((a, b) => Number(a) - Number(b))
            .map((index) => members[index])
            .filter((member) => member.nama !== '');

        return family;
    };

    const iksOf = (family) => {
        let ya = 0;
        let tidak = 0;
        indicatorIds.forEach((id) => {
            const value = family.indikator[id] || 'N';
            if (value === 'Y') ya++;
            else if (value === 'T') tidak++;
        });
        const answered = ya + tidak;
        if (answered === 0) return { label: 'Belum Lengkap', cls: 'na' };
        const score = ya / answered;

        if (score > 0.8) return { label: 'Keluarga Sehat', cls: 'sehat' };
        if (score >= 0.5) return { label: 'Pra-Sehat', cls: 'pra' };
        return { label: 'Tidak Sehat', cls: 'tidak' };
    };

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    const addressOf = (family) => [family.jalan, family.rt ? 'RT ' + family.rt : '', family.rw ? 'RW ' + family.rw : '', family.desa, family.kecamatan]
        .filter(Boolean).join(', ');

    const setError = (message) => {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('is-hidden', !message);
    };

    fileInput?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setError('');
        state.rawRows = [];
        state.drafts = [];
        resultCard.classList.add('is-hidden');

        try {
            const buffer = await file.arrayBuffer();
            const workbook = XLSX.read(buffer, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(sheet, { defval: '' });

            if (!rows.length) {
                filenameEl.classList.add('is-hidden');
                setError('File tidak berisi baris data. Pastikan baris pertama adalah header kolom.');
                return;
            }

            state.rawRows = rows;
            filenameEl.textContent = file.name + ' — ' + rows.length + ' baris ditemukan';
            filenameEl.classList.remove('is-hidden');
        } catch (e) {
            filenameEl.classList.add('is-hidden');
            setError('Gagal membaca file. Pastikan formatnya .xlsx, .xls, atau .csv.');
        }
    });

    processBtn?.addEventListener('click', () => {
        if (!state.rawRows.length) {
            setError('Pilih file Excel/CSV terlebih dahulu.');
            return;
        }

        state.drafts = state.rawRows.map((row, index) => {
            const family = mapRow(row);
            const ok = Boolean(family.kepala_keluarga);
            return {
                ok,
                family,
                label: family.kepala_keluarga ? addressOf(family) || '(tanpa alamat)' : 'Baris ' + (index + 1) + ': nama kepala keluarga kosong',
            };
        });

        renderDrafts();
    });

    const renderDrafts = () => {
        const okCount = state.drafts.filter((draft) => draft.ok).length;
        const failCount = state.drafts.length - okCount;

        resultSummary.textContent = okCount + ' baris siap disimpan' + (failCount ? ', ' + failCount + ' baris dilewati (perlu diisi manual).' : '.');

        draftsEl.innerHTML = state.drafts.map((draft, index) => {
            if (!draft.ok) {
                return `<div class="draft-row failed">
                    <div class="info"><strong>Baris gagal dipetakan</strong><span>${esc(draft.label)}</span></div>
                    <div class="actions"><button type="button" class="btn danger small" data-excel-remove="${index}">Hapus</button></div>
                </div>`;
            }

            const iks = iksOf(draft.family);
            return `<div class="draft-row">
                <div class="info">
                    <strong>${esc(draft.family.kepala_keluarga)}</strong>
                    <span>${esc(draft.label)} &middot; ${draft.family.anggota.length} anggota &middot; <span class="badge ${iks.cls}" style="padding:1px 8px;">${iks.label}</span></span>
                </div>
                <div class="actions"><button type="button" class="btn danger small" data-excel-remove="${index}">Hapus</button></div>
            </div>`;
        }).join('');

        resultCard.classList.remove('is-hidden');
    };

    draftsEl?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-excel-remove]');
        if (!button) return;
        state.drafts.splice(Number(button.getAttribute('data-excel-remove')), 1);
        renderDrafts();
    });

    discardBtn?.addEventListener('click', () => {
        state.rawRows = [];
        state.drafts = [];
        if (fileInput) fileInput.value = '';
        filenameEl.classList.add('is-hidden');
        resultCard.classList.add('is-hidden');
        setError('');
    });

    saveBtn?.addEventListener('click', async () => {
        const rows = state.drafts.filter((draft) => draft.ok).map((draft) => draft.family);
        if (!rows.length) return;

        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ rows }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Impor gagal.');
            }

            window.location.href = indexUrl;
        } catch (e) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Simpan ke Data Keluarga';
            setError(e.message || 'Impor gagal. Coba lagi.');
        }
    });

    document.getElementById('excel-template')?.addEventListener('click', () => {
        const header = [
            'Nama Kepala Keluarga', 'No KK', 'Alamat Jalan/Dusun', 'RT', 'RW', 'Desa/Kelurahan', 'Kecamatan',
            'Nama Anggota 1', 'Umur 1', 'JK 1 (L/P)', 'Hubungan 1',
            'Nama Anggota 2', 'Umur 2', 'JK 2 (L/P)', 'Hubungan 2',
            'KB', 'Bersalin di Faskes', 'Imunisasi Dasar Lengkap', 'ASI Eksklusif', 'Balita Dipantau',
            'TB Berobat Standar', 'Hipertensi Berobat Teratur', 'Gangguan Jiwa Diobati',
            'Tidak Merokok', 'Anggota JKN', 'Akses Air Bersih', 'Jamban Sehat',
            'Surveyor', 'Tanggal Kunjungan', 'Catatan',
        ];
        const sample = [
            'Sutrisno', '3204xxxxxxxxxxxx', 'Jl. Anggrek No 12', '03', '05', 'Cibiru', '',
            'Wati', '34', 'P', 'Istri',
            'Dimas', '6', 'L', 'Anak',
            'Ya', 'Ya', 'Ya', 'Tidak Berlaku', 'Tidak Berlaku',
            'Tidak Berlaku', 'Tidak Berlaku', 'Tidak Berlaku',
            'Ya', 'Ya', 'Ya', 'Ya',
            '', '', 'Contoh baris — hapus/ganti sebelum diisi data asli',
        ];

        const worksheet = XLSX.utils.aoa_to_sheet([header, sample]);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Data Keluarga');
        XLSX.writeFile(workbook, 'Template_Data_Keluarga_PISPK.xlsx');
    });
})();

/* ============================= GRAFIK KARTESIUS ============================= */

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
}

function renderBarChart(container, rows) {
    if (!container) return;

    if (!rows || !rows.length) {
        container.innerHTML = '<p class="chart-empty">Belum ada data.</p>';
        return;
    }

    const barWidth = 46;
    const gap = 20;
    const padLeft = 46;
    const padRight = 24;
    const padTop = 26;
    const padBottom = 96;
    const plotHeight = 220;
    const plotWidth = Math.max(rows.length * (barWidth + gap) - gap, 240);
    const width = padLeft + plotWidth + padRight;
    const height = padTop + plotHeight + padBottom;
    const baseline = padTop + plotHeight;

    const colorFor = (pct) => {
        if (pct === null) return '#c3c8c1';
        if (pct > 80) return '#2f7d4f';
        if (pct >= 50) return '#d99a2b';
        return '#c0492b';
    };

    const grid = [0, 25, 50, 75, 100].map((tick) => {
        const y = baseline - (plotHeight * tick) / 100;
        return `<line class="grid-line" x1="${padLeft}" y1="${y}" x2="${padLeft + plotWidth}" y2="${y}"></line>`
            + `<text class="axis-label" x="${padLeft - 8}" y="${y + 4}" text-anchor="end">${tick}%</text>`;
    }).join('');

    const bars = rows.map((row, index) => {
        const pct = row.pct === null || row.pct === undefined ? null : Math.round(row.pct);
        const barHeight = (plotHeight * (pct ?? 0)) / 100;
        const x = padLeft + index * (barWidth + gap);
        const y = baseline - barHeight;
        const anchor = x + barWidth / 2;
        const label = row.label.length > 18 ? escapeHtml(row.label.slice(0, 17)) + '…' : escapeHtml(row.label);

        return `<g>`
            + `<rect x="${x}" y="${y}" width="${barWidth}" height="${Math.max(barHeight, 2)}" rx="4" fill="${colorFor(pct)}">`
            + `<title>${escapeHtml(row.tooltip || row.label)}: ${pct === null ? 'n/a' : pct + '%'}</title></rect>`
            + `<text class="bar-value" x="${anchor}" y="${y - 6}" text-anchor="middle">${pct === null ? '—' : pct + '%'}</text>`
            + `<text class="bar-label" x="${anchor}" y="${baseline + 16}" text-anchor="end" transform="rotate(-35 ${anchor} ${baseline + 16})">${label}</text>`
            + `</g>`;
    }).join('');

    container.innerHTML = `<svg class="wilayah-chart" viewBox="0 0 ${width} ${height}" width="${width}" height="${height}" role="img" aria-label="Grafik batang kartesius">`
        + grid
        + `<line class="axis-line" x1="${padLeft}" y1="${baseline}" x2="${padLeft + plotWidth}" y2="${baseline}"></line>`
        + `<line class="axis-line" x1="${padLeft}" y1="${padTop}" x2="${padLeft}" y2="${baseline}"></line>`
        + bars
        + `</svg>`;
}

function toBarRows(rows) {
    return rows.map((row) => ({
        label: row.label,
        pct: row.avg === null || row.avg === undefined ? null : Math.round(row.avg * 100),
        tooltip: row.label + ': ' + (row.avg === null ? 'belum ada data' : Math.round(row.avg * 100) + '%') + ' · ' + row.n + ' KK',
    }));
}

(function () {
    const app = document.querySelector('[data-wilayah-app]');
    if (!app) return;

    const dataEl = document.getElementById('wilayah-data');
    let data = {};

    try {
        data = JSON.parse(dataEl?.textContent || '{}');
    } catch (e) {
        data = {};
    }

    const chartEl = document.getElementById('wilayah-chart');
    const buttons = Array.from(app.querySelectorAll('[data-wilayah-level]'));
    const panels = Array.from(app.querySelectorAll('[data-wilayah-panel]'));
    if (!buttons.length) return;

    const activate = (level) => {
        buttons.forEach((button) => button.classList.toggle('active', button.dataset.wilayahLevel === level));
        panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.wilayahPanel !== level));
        renderBarChart(chartEl, toBarRows(data[level]?.rows || []));
    };

    buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.wilayahLevel)));

    activate(buttons[0].dataset.wilayahLevel);
})();

/* ============================= INDIKATOR TOGGLE ============================= */

(function () {
    const app = document.querySelector('[data-indikator-app]');
    if (!app) return;

    const dataEl = document.getElementById('indikator-data');
    let rows = [];

    try {
        rows = JSON.parse(dataEl?.textContent || '[]').map((item) => ({
            label: item.q,
            pct: item.pct,
            tooltip: item.q,
        }));
    } catch (e) {
        rows = [];
    }

    const chartEl = document.getElementById('indikator-chart');
    const buttons = Array.from(app.querySelectorAll('[data-indikator-mode]'));
    const panels = Array.from(app.querySelectorAll('[data-indikator-panel]'));
    if (!buttons.length) return;

    const activate = (mode) => {
        buttons.forEach((button) => button.classList.toggle('active', button.dataset.indikatorMode === mode));
        panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.indikatorPanel !== mode));
        if (mode === 'kartesius') renderBarChart(chartEl, rows);
    };

    buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.indikatorMode)));

    activate('batang');
})();

/* ============================= INDIKATOR KARTESIUS ============================= */

(function () {
    const app = document.querySelector('[data-indikator-app]');
    if (!app) return;

    const dataEl = document.getElementById('indikator-data');
    let rows = [];

    try {
        rows = JSON.parse(dataEl?.textContent || '[]');
    } catch (e) {
        rows = [];
    }

    const chartEl = document.getElementById('indikator-chart');
    const buttons = Array.from(app.querySelectorAll('[data-indikator-mode]'));
    const panels = Array.from(app.querySelectorAll('[data-indikator-panel]'));
    if (!buttons.length) return;

    const activate = (mode) => {
        buttons.forEach((button) => button.classList.toggle('active', button.dataset.indikatorMode === mode));
        panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.indikatorPanel !== mode));

        if (mode === 'kartesius') {
            renderBarChart(chartEl, rows.map((r) => ({
                label: r.id.toUpperCase(),
                pct: r.pct === null ? null : Math.round(r.pct * 100),
                tooltip: r.q + ': ' + (r.pct === null ? 'n/a' : Math.round(r.pct * 100) + '%'),
            })));
        }
    };

    buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.indikatorMode)));

    activate('batang');
})();
