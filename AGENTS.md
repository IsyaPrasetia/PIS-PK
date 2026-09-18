# AGENTS.md

Laravel 13 app (Framework 13.32, PHP ^8.3) implementing **PIS-PK** — Pendataan Keluarga Sehat. Server-rendered Blade + vanilla JS (bundled by Vite); no Inertia/Livewire. The original static prototype lives at `resources/template/pispk-app (3).html` and is the UI/behavior reference.

## Domain model (read before touching data logic)

- **12 indicators** are the core domain. Single source of truth: `app/Support/Indikator.php` (`all()`, `ids()`, `forDomain()`, `number()`). Never hard-code indicator lists elsewhere.
- `families` table stores each answer as a column `ind_<id>` (`Y`/`T`/`N`) plus address/KK/notes + audit `created_by`/`updated_by` (FK users). `family_members` holds members (`family_id` FK cascade).
- **IKS** (Indeks Keluarga Sehat) = `Y / (Y + T)`; `N` (tidak berlaku) is excluded. Thresholds: `> 0.8` sehat, `0.5–0.8` pra, `< 0.5` tidak, no answers = belum. Logic lives in `App\Models\Family::iks()` / `iksStatsFor()` — reuse it, don't reimplement in views (rekap calls it inline already).
- `Family::getFillable()` merges `ind_*` dynamically; create/update flow through `FamilyRequest` (indicator rules generated per id).

## Auth & roles (v2 feature)

- **Database is MySQL** (`DB_CONNECTION=mysql`, db `pispk`, root/no password, `127.0.0.1:3306`); tests still use sqlite in-memory (`phpunit.xml`). Local SQLite `database/database.sqlite` may not exist anymore — don't rely on it.
- Full auth: all app routes inside `Route::middleware('auth')`; only `/login` is guest. `LoginController` (login/logout), view `resources/views/auth/login.blade.php`.
- **Roles** (`app/Enums/UserRole.php`, backed enum): `superadmin` (0) > `admin_wilayah` (1) > `admin_rw` (2) > `admin_rt` (3) > `petugas` (4). Has `label()`, `level()`, `options()`.
- `users` carry `role`, `kecamatan`, `desa`, `rw`, `rt`, and `created_by`. Helpers on `App\Models\User`: `role()`, `isSuperadmin()`, `isAdmin()`, `canManageUsers()` (superadmin + admin_wilayah), `outranks()`, `canCreateRole()`, `scopeLabel()`, `matchesFamily()`, `canViewFamily()`, `canCreateFamily()`, `canEditFamily()`.
- **Scoping**: `Family::scopeScopedFor(User)` filters by hierarchy — superadmin=all, admin_wilayah=kecamatan, admin_rw=kecamatan+desa+rw, admin_rt/petugas=+rt. `RegionScope` logic central in `User::matches()`. Use `scopedFor(Auth::user())` on Family queries (dashboard, keluarRam, rekap, impor already do).
- **Petugas (field worker)**: input-only section — read + create new, edit only own records within 24h (`canEditFamily`), after that route through admin RT/RW. Nav limited to Data Keluarga, Input Keluarga, Input AI (`layouts/app.blade.php` `$isPetugas`).
- **User management**: `/pengguna` CRUD via `UserController` + `UserRequest`. Superadmin manages everyone; admin_wilayah only manages `admin_rw`/`admin_rt`/`petugas` within own kecamatan (kecamatan forced, readonly in form). Form view `resources/views/users/form.blade.php` — **wilayah fields (kecamatan/desa/rw/rt) are cascading dropdowns from master data**, no free-form typing.
- **Master data wilayah**: table `wilayah` (`kecamatan`, `desa`, `rw`, `rt`; kolom desa/rw/rt nullable, kombinasi unik) + model `App\Models\Wilayah` with `kecamatans()`, `desas($kecamatan)`, `rws($kecamatan,$desa)`, `rts($kecamatan,$desa,$rw)`, dan `ensure($kecamatan,$desa,$rw,$rt)` (firstOrCreate, anti-dobel). Halaman **`/wilayah`** (`WilayahController` + `resources/views/wilayah/index.blade.php`) untuk CRUD master wilayah (superadmin + admin_wilayah, admin_wilayah terkunci ke kecamatannya). Form pengguna punya tombol **+ Tambah** per dropdown yang POST JSON ke `wilayah.store`. `WilayahSeeder` seeds Cileunyi/Cileunyi Kulon/Cimenyan/Cilengkrang. `UserController::wilayahTree()` membangun struktur cascade. **Impor Excel (`ImporController@store`) otomatis memanggil `Wilayah::ensure()` per baris** sehingga wilayah baru ikut tercatat. Jangan hard-code daftar kecamatan/desa/rw/rt di view.
- **Dashboard filters** (`DashboardController::index`): query params `kecamatan`/`desa`/`rw`/`rt` cascade drill-down + `domain` (indicator group from `Indikator::domains()`). Applies to stats + indicator bars/cart and rekap. `wilayahTree` (from scoped families, not master data) drives the cascade dropdown options; only deeper options show once parent picked. Cascade **tidak auto-submit** — user mengisi lalu klik tombol **Cari** (tombol Reset selalu tampil).
- Media notes: `canEditFamily` for petugas uses `created_by === Auth::id()` && `created_at` < 24h — in tests use `forceFill(['created_at' => now()->subHours(2)])->save()` because `created_at` is not fillable.

## Routes / controllers

- `/` Dashboard · `/keluarga` list+search (`?q=`) · `/keluarga/tambah|{id}/ubah` form (PUT/DELETE) · `/rekap` hierarchical Desa→RW→RT→KK · `/indikator` reference · `/impor` Excel import · `/ai` AI note extraction · `/login` · `/logout` · `/pengguna` user management.
- Excel import parses **client-side** with SheetJS (`xlsx`, dev dep) and POSTs normalized JSON to `/impor` (`ImporController@store`); template headers are the contract. AI drafts (dari `NoteExtractor` lokal atau Anthropic) di-pass ke create form via `session('ai_draft')`.

## AI lokal (`NoteExtractor`)

- Default engine adalah **mesin ekstraksi lokal deterministik** `app/Support/NoteExtractor.php` (regex + leksikon kata kunci + aturan keberlakuan 12 indikator) — **tanpa API key, offline**. `AiController::extract` memakainya (default), struktur draft identik dengan output Anthropic lama: `kepala_keluarga, no_kk, jalan, rt, rw, desa, kecamatan, catatan, anggota[], indikator[Y/T/N/?→N], flagged[]`.
- Kode Anthropic tetap ada di `AiController` (`extractWithAnthropic`, `normalize`, `prompt`) tapi **disisihkan/dormant**: aktif hanya bila `AI_ENGINE=anthropic` (`config/services.ai.engine`, default `local`) DAN key terisi.
- Halaman `/ai` tidak lagi menampilkan notice konfigurasi; tombol selalu aktif.
- **Feedback loop (data training ML)**: `POST /keluarga` yang berasal dari draft AI menyertakan hidden `ai_note` (catatan asli) + `ai_draft` (JSON draft). `FamilyController@store` menangkapnya → menulis ke tabel **`ai_training_logs`** (`App\Models\AiTrainingLog`): `catatan`, `draft_json`, `final_json` (data final yang disimpan = koreksi petugas), `created_by`, `created_at`. Normal CRUD tanpa `ai_note` tidak membuat log.

## ML layer (Rubix ML) — `ai:train`

- Pipeline ML lokal: `WordCountVectorizer` (bag-of-words, tokenizer `Word`) → `SoftmaxClassifier` per indikator, dibungkus `Pipeline` + `PersistentModel` (persister `Filesystem`), disimpan ke `storage/app/models/ai/ind_<id>.rbx`. Tidak ada MultinomialNB di Rubix v2.5 — Softmax adalah subtitute multiclass-nya.
- **`php artisan ai:train`** (`TrainAiModelsCommand`) melatih model per indikator dari `ai_training_logs`; tombol `--indicator=kb` utk satu indikator. Minimal **20 sampel** per indikator (`AiTrainer::MIN_SAMPLES_PER_INDICATOR`) sebelum dilatih; tanpa cukup data dilaporkan `belum cukup` dan model lama diberi @unlink dulu baru ditimpa agar tak sisa.
- **`AiPredictor`** (`predict(string $id, string $note)`) memuat model (cache per-request) & mengembalikan `['value' => Y/T/N, 'confidence' => float]` hanya bila confidence ≥ **0.75** (`services.ai.ml_confidence`). Tanpa model file atau confidence rendah → `null`.
- **Hybrid (aturan primer, ML pelengkap)**: `AiController::extract` → `NoteExtractor` dulu, lalu `refineWithMl()` memanggil `AiPredictor` **hanya untuk indikator yang masih ber-flag "perlu dicek"**; ML menentukan nilainya bila yakin, sisanya tetap DFLt. Ini menjaga aturan (yang sudah andal) tidak ditimpa ML yang belum matang; `services.ai.ml_enabled=false` mematikan refine.
- Config: `services.ai.engine` (local/anthropic), `services.ai.ml_enabled`, `services.ai.ml_confidence`, `services.ai.models_path`.
- Seeder **`AiTrainingLogSeeder`** (42 sampel realistis, dipanggil di `DatabaseSeeder`) mengisi dataset awal agar `ai:train` langsung berfungsi; hapus dari DB bila mau mulai dari data nyata murni.
- Rubix ML masih memakai `ReflectionProperty::setAccessible()` → deprecation noise di stdout tinker (PHP 8.1+, framework sudah menangani); tidak memengaruhi web atau test.
- Catatan ekstraksi: `%` `flagged` = relevan tapi tak jelas → di-render "perlu dicek" di form. Negasi (`tidak/belum/tidak ada yang`) di-screen tiap pola; `rokok` khusus (indikator negatif: "tidak ada yang merokok" = Y, cek `yes` dulu). Akronim diblacklist supaya tak jadi nama anggota.
- Family list/edit actions gated by `canEditFamily` (`families/index.blade.php`); `FamilyController` also stamps `created_by`/`updated_by` and prefills user scope via `mergeScope()`.
- `ForceRequestRootUrl` middleware (registered with `append()` in `bootstrap/app.php`, after `TrustProxies`) + `trustProxies(at: '*')` so asset URLs use the current host through cloudflared tunnel (`X-Forwarded-*`).
- Tunnel URL: `https://auditor-nursing-ken-den.trycloudflare.com` (quick tunnel — alamat berubah setiap restart cloudflared); `APP_URL=http://localhost:8000`.

## Commands

- `composer setup` — full first-time setup: install, copy `.env`, `key:generate`, migrate, `npm install --ignore-scripts`, `npm run build`.
- `composer dev` — `php artisan dev` (concurrent HTTP + Vite). Prefer this over `php artisan serve`.
- `composer test` — `php artisan config:clear` then `php artisan test`. Single test: `php artisan test --filter=FamilyTest`.
- `php artisan migrate:fresh --seed` — reset + load seeders in MySQL: `FamilySeeder` (demo families) + `UserSeeder` (demo accounts) + `AiTrainingLogSeeder` (42 catatan training ML). `DatabaseSeeder` does NOT use the `User` factory.
- `vendor\bin\pint` (no composer alias); `--test` to check only.
- `npm run build` / `npm run dev` — frontend. Rebuild after JS/CSS changes; Blade-only edits need no rebuild.
- Demo accounts (password `rahasia123`): `superadmin@pispk.test` (all), `wilayah@pispk.test` (Cileunyi), `rw@pispk.test` (Cileunyi/Cibiru/RW05), `rt@pispk.test` (+RT03), `petugas@pispk.test` (input-only, Cileunyi/Cibiru/RW05/RT03).

## Environment / gotchas

- MySQL via Laragon (root, no password). `.env`: DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=pispk, DB_USERNAME=root, DB_PASSWORD=.
- Vite + Tailwind CSS v4 via `@tailwindcss/vite`. CSS-first: **no `tailwind.config.js`**; PIS-PK design tokens + component classes are appended after `@import 'tailwindcss'` in `resources/css/app.css` — classes mirror the template, so prefer them over raw Tailwind utilities. Auth + user-chip styles live there too.
- `.npmrc` sets `ignore-scripts=true`; if a build fails on a missing native binary (e.g. esbuild), reinstall overriding that flag.
- Not a git repository (`.git` absent, `.gitignore` present).
- `package.json` still has `opencode-ai` dev dependency pending removal (unrelated to app runtime).

## Notes

- `AGENTS.md` and `CLAUDE.md` originally shipped the stock "Laravel Boost" placeholder. If you adopt Boost, `composer require laravel/boost --dev` + `php artisan boost:install` regenerates app-tailored agent guidelines — re-read this file after.