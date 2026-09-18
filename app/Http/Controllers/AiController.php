<?php

namespace App\Http\Controllers;

use App\Support\AiPredictor;
use App\Support\Indikator;
use App\Support\NoteExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class AiController extends Controller
{
    public function index(): View
    {
        return view('ai.index');
    }

    public function extract(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'catatan' => ['required', 'string', 'min:10'],
        ]);

        // Mesin lokal (default). Jalur Anthropic dipertahankan tapi disisihkan —
        // aktif hanya bila AI_ENGINE=anthropic DAN API key terisi.
        if (config('services.ai.engine') === 'anthropic' && (config('services.anthropic.key') ?? null)) {
            $draft = $this->extractWithAnthropic($data['catatan']);
        } else {
            $draft = NoteExtractor::extract($data['catatan']);
            $draft = $this->refineWithMl($draft);
        }

        session()->put('ai_draft', $draft);

        return redirect()
            ->route('families.create')
            ->with('success', 'Hasil AI dimuat. Periksa dan koreksi sebelum menyimpan.');
    }

    /**
     * Koreksi hasil aturan dengan model ML bila tersedia: indikator yang
     * masih ber-flag (tidak jelas) dilengkapi ketika model yakin (confidence
     * ≥ ambang). Tanpa model atau confidence rendah, nilai aturan menang.
     *
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function refineWithMl(array $draft): array
    {
        if (! config('services.ai.ml_enabled', true)) {
            return $draft;
        }

        $predictor = new AiPredictor;
        $catatan = $draft['catatan'] ?? '';

        if ($catatan === '') {
            return $draft;
        }

        foreach ($draft['flagged'] ?? [] as $id => $_) {
            $guess = $predictor->predict((string) $id, (string) $catatan);

            // ML hanya menyelesaikan keraguan menjadi Ya/Tidak. Prediksi "N"
            // (tidak berlaku) tidak menghapus flag — menegaskan hal negatif dari
            // catatan yang tidak jelas adalah tugas petugas, bukan model yang
            // bias ke kelas mayoritas (N).
            if ($guess === null || $guess['value'] === 'N') {
                continue;
            }

            $draft['indikator'][$id] = $guess['value'];
            unset($draft['flagged'][$id]);
        }

        return $draft;
    }

    private function extractWithAnthropic(string $note): array
    {
        $key = config('services.anthropic.key');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 1500,
                'messages' => [
                    ['role' => 'user', 'content' => $this->prompt($note)],
                ],
            ])->throw()->json();
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }

        $raw = collect($response['content'] ?? [])
            ->map(fn (array $block) => $block['text'] ?? '')
            ->implode('');

        $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($raw)));
        $parsed = json_decode($raw, true);

        if (! is_array($parsed)) {
            throw new \RuntimeException('Hasil AI tidak dapat dibaca.');
        }

        return $this->normalize($parsed);
    }

    /**
     * Ubah hasil JSON AI menjadi struktur draft yang dipakai form keluarga.
     */
    private function normalize(array $parsed): array
    {
        $indikator = [];
        $flagged = [];

        foreach (Indikator::ids() as $id) {
            $value = $parsed['indikator'][$id] ?? 'N';

            if ($value === '?') {
                $indikator[$id] = '?';
                $flagged[$id] = true;
            } elseif (in_array($value, ['Y', 'T', 'N'], true)) {
                $indikator[$id] = $value;
            } else {
                $indikator[$id] = '?';
                $flagged[$id] = true;
            }
        }

        $anggota = collect($parsed['anggota'] ?? [])
            ->filter(fn ($member) => is_array($member) && trim((string) ($member['nama'] ?? '')) !== '')
            ->map(fn (array $member) => [
                'nama' => (string) ($member['nama'] ?? ''),
                'umur' => $member['umur'] === null || $member['umur'] === '' ? '' : (string) $member['umur'],
                'jenis_kelamin' => ($member['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L',
                'hubungan' => (string) ($member['hubungan'] ?? ''),
                'nik' => (string) ($member['nik'] ?? ''),
            ])
            ->values()
            ->all();

        return [
            'kepala_keluarga' => (string) ($parsed['kepala_keluarga'] ?? ''),
            'no_kk' => (string) ($parsed['no_kk'] ?? ''),
            'jalan' => (string) ($parsed['alamat']['jalan'] ?? ''),
            'rt' => (string) ($parsed['alamat']['rt'] ?? ''),
            'rw' => (string) ($parsed['alamat']['rw'] ?? ''),
            'desa' => (string) ($parsed['alamat']['desa'] ?? ''),
            'kecamatan' => (string) ($parsed['alamat']['kecamatan'] ?? ''),
            'catatan' => (string) ($parsed['catatan'] ?? ''),
            'anggota' => $anggota,
            'indikator' => $indikator,
            'flagged' => $flagged,
        ];
    }

    private function prompt(string $note): string
    {
        $indicatorList = collect(Indikator::ids())->map(fn (string $id) => '"'.$id.'"')->implode(', ');

        return <<<PROMPT
        Kamu membantu petugas Puskesmas mengubah catatan kunjungan rumah menjadi data terstruktur untuk program PIS-PK (Program Indonesia Sehat dengan Pendekatan Keluarga).

        Catatan kunjungan:
        """
        {$note}
        """

        Tugas: keluarkan HANYA satu objek JSON valid (tanpa teks lain, tanpa markdown, tanpa backtick) dengan struktur persis berikut:
        {
          "kepala_keluarga": string,
          "no_kk": string,
          "alamat": {"jalan": string, "rt": string, "rw": string, "desa": string, "kecamatan": string},
          "anggota": [{"nama": string, "umur": number atau null, "jenis_kelamin": "L" atau "P", "hubungan": string, "nik": string}],
          "indikator": { {$indicatorList}: masing-masing salah satu dari "Y" (ya/patuh), "T" (tidak/belum patuh), "N" (tidak berlaku untuk keluarga ini), atau "?" (relevan tapi tidak disebutkan di catatan, perlu verifikasi) },
          "catatan": string ringkas berisi hal penting yang tidak masuk field lain
        }

        Aturan penilaian indikator:
        - "kb": hanya berlaku jika ada pasangan usia subur; jika tidak ada, isi "N".
        - "bersalin": hanya berlaku jika ada riwayat persalinan yang relevan; jika tidak ada ibu hamil/bersalin, isi "N".
        - "imunisasi" dan "asi": hanya berlaku jika ada bayi/balita; jika tidak ada, isi "N".
        - "balita": hanya berlaku jika ada anak balita.
        - "tb", "hipertensi", "jiwa": hanya berlaku jika ada anggota dengan kondisi tersebut; jika tidak disebutkan sama sekali dan tidak ada indikasi, isi "N".
        - "rokok", "jkn", "air", "jamban": selalu berlaku untuk semua keluarga.
        Jika field tidak disebutkan dalam catatan, gunakan string kosong "" untuk teks, null untuk angka, dan array kosong [] jika tidak ada anggota yang disebut. Jangan mengarang NIK atau nomor KK. Balas hanya dengan JSON.
        PROMPT;
    }
}
