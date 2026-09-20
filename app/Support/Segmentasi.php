<?php

namespace App\Support;

use App\Models\Family;

/**
 * Rule engine segmentasi keluarga berdasarkan Rancangan Segmentasi Keluarga IKS.
 *
 * Segmentasi multi-label berbasis aturan (Tahap 1) — tidak menebak diagnosis,
 * hanya membantu prioritas administrasi/edukasi/verifikasi. Pisahkan antara
 * segmen kebutuhan dan segmen kualitas data.
 */
final class Segmentasi
{
    public const BATAS_KADALUARSA_BULAN = 6;

    public const MIN_JAWABAN_LENGKAP = 10;

    public const MIN_JAWABAN_VERIFIKASI = 6;

    public const KELOMPOK_KEBUTUHAN = 'kebutuhan';

    public const KELOMPOK_KUALITAS = 'kualitas';

    /**
     * Katalog seluruh segmen: id → label, kelompok, dan deskripsi singkat.
     *
     * @return array<string, array{label: string, kelompok: string, deskripsi: string}>
     */
    public static function katalog(): array
    {
        return [
            'sehat_stabil' => [
                'label' => 'Keluarga Sehat & Stabil',
                'kelompok' => self::KELOMPOK_KEBUTUHAN,
                'deskripsi' => 'Sebagian besar indikator terisi "Ya", tidak ada masalah prioritas. Cukup pemantauan rutin.',
            ],
            'penyakit_kronis' => [
                'label' => 'Masalah Penyakit Kronis',
                'kelompok' => self::KELOMPOK_KEBUTUHAN,
                'deskripsi' => 'Ada penderita TB, hipertensi, atau gangguan jiwa berat yang belum tertangani/berobat sesuai standar.',
            ],
            'ibu_anak' => [
                'label' => 'Kebutuhan Ibu & Anak',
                'kelompok' => self::KELOMPOK_KEBUTUHAN,
                'deskripsi' => 'KB, persalinan di faskes, imunisasi, ASI eksklusif, atau pemantauan pertumbuhan balita belum terpenuhi.',
            ],
            'sanitasi' => [
                'label' => 'Sanitasi & Lingkungan',
                'kelompok' => self::KELOMPOK_KEBUTUHAN,
                'deskripsi' => 'Akses air bersih, jamban sehat, atau kebiasaan merokok anggota keluarga bermasalah.',
            ],
            'perlindungan_sosial' => [
                'label' => 'Perlindungan Sosial',
                'kelompok' => self::KELOMPOK_KEBUTUHAN,
                'deskripsi' => 'Keluarga belum terdaftar sebagai peserta JKN.',
            ],
            'data_lengkap' => [
                'label' => 'Data Lengkap',
                'kelompok' => self::KELOMPOK_KUALITAS,
                'deskripsi' => 'Seluruh 12 indikator terisi dan identitas wilayah lengkap.',
            ],
            'data_perlu_verifikasi' => [
                'label' => 'Data Perlu Verifikasi',
                'kelompok' => self::KELOMPOK_KUALITAS,
                'deskripsi' => 'Banyak indikator belum terisi atau data anggota/wilayah tidak lengkap.',
            ],
            'duplikasi_potensial' => [
                'label' => 'Duplikasi Potensial',
                'kelompok' => self::KELOMPOK_KUALITAS,
                'deskripsi' => 'Nama kepala keluarga sama dengan keluarga lain di wilayah yang sama.',
            ],
            'data_kadaluarsa' => [
                'label' => 'Data Kadaluarsa',
                'kelompok' => self::KELOMPOK_KUALITAS,
                'deskripsi' => 'Data sudah lama tidak diperbarui (di atas '.self::BATAS_KADALUARSA_BULAN.' bulan).',
            ],
        ];
    }

    /**
     * Katalog yang dikelompokkan per jenis segmen.
     *
     * @return array<string, array<int, array{id: string, label: string, deskripsi: string}>>
     */
    public static function perKelompok(): array
    {
        $result = [];

        foreach (self::urutanSegmen() as $id) {
            $item = self::katalog()[$id];
            $result[$item['kelompok']][] = [
                'id' => $id,
                'label' => $item['label'],
                'deskripsi' => $item['deskripsi'],
            ];
        }

        return $result;
    }

    public static function label(string $id): string
    {
        return self::katalog()[$id]['label'] ?? $id;
    }

    /**
     * Urutan tampil segmen yang stabil (kebutuhan lebih dulu).
     *
     * @return list<string>
     */
    private static function urutanSegmen(): array
    {
        return [
            'sehat_stabil',
            'penyakit_kronis',
            'ibu_anak',
            'sanitasi',
            'perlindungan_sosial',
            'data_lengkap',
            'data_perlu_verifikasi',
            'duplikasi_potensial',
            'data_kadaluarsa',
        ];
    }

    /**
     * Analisis segmentasi satu keluarga, mengikuti struktur output rancangan:
     * { iks, kategori_iks, segments, priority_score, priority_level, reasons, requires_human_review }.
     *
     * @param  array{is_duplikat?: bool}  $options  Sinyal duplikasi (opsional, hasil deteksi antar-keluarga).
     * @return array{
     *     keluarga_id: int,
     *     iks: float|null,
     *     kategori_iks: string,
     *     segments: list<string>,
     *     priority_score: int,
     *     priority_level: string,
     *     priority_level_label: string,
     *     reasons: list<string>,
     *     requires_human_review: bool,
     * }
     */
    public static function analisis(Family $family, array $options = []): array
    {
        $values = $family->indicatorValues();
        $iks = $family->iks();
        $answered = $iks['answered'];

        $belumTerisi = 0;

        foreach (Indikator::ids() as $id) {
            $raw = $family->getAttribute('ind_'.$id);

            if ($raw === null || $raw === '') {
                $belumTerisi++;
            }
        }

        $segments = [];
        $reasons = [];

        $chronic = [];
        if ($values['tb'] === 'T') {
            $chronic[] = 'Penderita tuberkulosis paru belum berobat sesuai standar';
        }
        if ($values['hipertensi'] === 'T') {
            $chronic[] = 'Penderita hipertensi belum melakukan pengobatan secara teratur';
        }
        if ($values['jiwa'] === 'T') {
            $chronic[] = 'Gangguan jiwa berat belum diobati atau ditelantarkan';
        }
        if ($chronic !== []) {
            $segments[] = 'penyakit_kronis';
            $reasons = array_merge($reasons, $chronic);
        }

        $ibuAnak = [];
        if ($values['kb'] === 'T') {
            $ibuAnak[] = 'Keluarga belum mengikuti program KB';
        }
        if ($values['bersalin'] === 'T') {
            $ibuAnak[] = 'Ibu belum melakukan persalinan di fasilitas kesehatan';
        }
        if ($values['imunisasi'] === 'T') {
            $ibuAnak[] = 'Imunisasi dasar bayi belum lengkap';
        }
        if ($values['asi'] === 'T') {
            $ibuAnak[] = 'ASI eksklusif belum diberikan';
        }
        if ($values['balita'] === 'T') {
            $ibuAnak[] = 'Pertumbuhan balita belum dipantau tiap bulan';
        }
        if ($ibuAnak !== []) {
            $segments[] = 'ibu_anak';
            $reasons = array_merge($reasons, $ibuAnak);
        }

        $sanitasi = [];
        if ($values['air'] === 'T') {
            $sanitasi[] = 'Belum ada akses / penggunaan sarana air bersih';
        }
        if ($values['jamban'] === 'T') {
            $sanitasi[] = 'Belum ada akses / penggunaan jamban sehat';
        }
        if ($values['rokok'] === 'T') {
            $sanitasi[] = 'Ada anggota keluarga yang merokok';
        }
        if ($sanitasi !== []) {
            $segments[] = 'sanitasi';
            $reasons = array_merge($reasons, $sanitasi);
        }

        if ($values['jkn'] === 'T') {
            $segments[] = 'perlindungan_sosial';
            $reasons[] = 'Keluarga belum menjadi peserta JKN';
        }

        $verify = [];
        if ($belumTerisi >= self::MIN_JAWABAN_VERIFIKASI) {
            $verify[] = 'Sebanyak '.$belumTerisi.' dari 12 indikator belum dijawab (kolom kosong)';
        }
        if ($family->members_count !== null && (int) $family->members_count === 0) {
            $verify[] = 'Tidak ada anggota keluarga yang tercatat';
        }
        if (trim((string) $family->kecamatan) === '' || trim((string) $family->desa) === '') {
            $verify[] = 'Wilayah (kecamatan/desa) belum lengkap';
        }
        if ($verify !== []) {
            $segments[] = 'data_perlu_verifikasi';
            $reasons = array_merge($reasons, $verify);
        }

        if (($options['is_duplikat'] ?? false) === true) {
            $segments[] = 'duplikasi_potensial';
            $reasons[] = 'Nama kepala keluarga sama dengan keluarga lain di wilayah yang sama';
        }

        if ($family->updated_at !== null && $family->updated_at->lt(now()->subMonths(self::BATAS_KADALUARSA_BULAN))) {
            $segments[] = 'data_kadaluarsa';
            $reasons[] = 'Data terakhir diperbarui '.($family->updated_at->diffInMonths(now()) + 1).' bulan lalu';
        }

        if ($belumTerisi === 0
            && trim((string) $family->kecamatan) !== ''
            && trim((string) $family->desa) !== ''
            && ! in_array('data_perlu_verifikasi', $segments, true)
            && ! in_array('duplikasi_potensial', $segments, true)) {
            $segments[] = 'data_lengkap';
            $reasons[] = 'Seluruh 12 indikator terisi dan identitas wilayah lengkap';
        }

        if ($iks['class'] === 'sehat'
            && $answered >= self::MIN_JAWABAN_LENGKAP
            && $chronic === []
            && $ibuAnak === []
            && $sanitasi === []
            && $values['jkn'] !== 'T'
            && ! in_array('data_perlu_verifikasi', $segments, true)
            && ! in_array('duplikasi_potensial', $segments, true)) {
            $segments[] = 'sehat_stabil';
            $reasons[] = 'Keluarga sehat, data cukup lengkap, tidak ada masalah prioritas';
        }

        $segments = array_values(array_unique(array_intersect(self::urutanSegmen(), $segments)));
        $level = self::level($score = self::hitungSkor($segments, $iks));

        return [
            'keluarga_id' => $family->id,
            'iks' => $iks['score'],
            'kategori_iks' => $iks['label'],
            'segments' => $segments,
            'priority_score' => $score,
            'priority_level' => $level,
            'priority_level_label' => self::levelLabel($level),
            'reasons' => $reasons,
            'requires_human_review' => in_array($level, ['tinggi', 'sangat_tinggi'], true)
                || in_array('data_perlu_verifikasi', $segments, true)
                || in_array('duplikasi_potensial', $segments, true)
                || in_array('data_kadaluarsa', $segments, true),
        ];
    }

    /**
     * Skor prioritas 0–100 sesuai rancangan:
     * masalah kesehatan + ibu-anak + sanitasi + jkn + kualitas data + urgensi IKS.
     *
     * @param  list<string>  $segments
     * @param  array{class: string, score: float|null}  $iks
     */
    private static function hitungSkor(array $segments, array $iks): int
    {
        $score = 0;

        $bobot = [
            'penyakit_kronis' => 30,
            'ibu_anak' => 20,
            'sanitasi' => 15,
            'perlindungan_sosial' => 10,
            'data_perlu_verifikasi' => 10,
            'data_kadaluarsa' => 5,
            'duplikasi_potensial' => 5,
        ];

        foreach ($bobot as $id => $bobotSkor) {
            if (in_array($id, $segments, true)) {
                $score += $bobotSkor;
            }
        }

        $iksScore = $iks['score'];

        if ($iksScore !== null) {
            if ($iksScore < 0.5) {
                $score += 10;
            } elseif ($iksScore < 0.8) {
                $score += 5;
            }
        }

        return min(100, $score);
    }

    public static function level(int $score): string
    {
        if ($score >= 80) {
            return 'sangat_tinggi';
        }

        if ($score >= 60) {
            return 'tinggi';
        }

        if ($score >= 30) {
            return 'sedang';
        }

        return 'rendah';
    }

    public static function levelLabel(string $level): string
    {
        return match ($level) {
            'sangat_tinggi' => 'Sangat Tinggi',
            'tinggi' => 'Tinggi',
            'sedang' => 'Sedang',
            default => 'Rendah',
        };
    }

    /**
     * Deteksi duplikasi potensial: nama kepala keluarga yang sama
     * (dinormalisasi) di kombinasi wilayah yang sama.
     *
     * @param  iterable<Family>  $families
     * @return array<int, true>
     */
    public static function idDuplikatPotensial(iterable $families): array
    {
        $kunci = [];

        foreach ($families as $family) {
            $key = implode('|', [
                mb_strtolower(trim((string) $family->kepala_keluarga)),
                trim((string) $family->kecamatan),
                trim((string) $family->desa),
                trim((string) $family->rw),
                trim((string) $family->rt),
            ]);

            if (trim((string) $family->kepala_keluarga) === '') {
                continue;
            }

            $kunci[$key][] = $family->id;
        }

        $duplikat = [];

        foreach ($kunci as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplikat[$id] = true;
                }
            }
        }

        return $duplikat;
    }
}
