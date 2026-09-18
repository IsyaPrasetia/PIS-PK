<?php

namespace Database\Seeders;

use App\Models\AiTrainingLog;
use App\Support\Indikator;
use Illuminate\Database\Seeder;

/**
 * Seed data training ML (ai_training_logs) dengan contoh catatan kunjungan
 * realistis dan jawaban final yang benar — dipakai `php artisan ai:train`
 * untuk melatih classifier per indikator.
 */
class AiTrainingLogSeeder extends Seeder
{
    /**
     * Contoh catatan → jawaban benar (hanya indikator yang relevan; sisanya N).
     *
     * @var array<int, array{catatan: string, indikator: array<string, string>}>
     */
    private const SAMPLES = [
        ['catatan' => 'Keluarga Bapak Sutrisno, istri Wati 34 th ikut KB suntik, anak Dimas 6 th imunisasi lengkap saat bayi, masih ASI untuk Nadia 1 th yang rutin ditimbang, tidak ada yang merokok, punya BPJS, air dari PDAM, jamban sendiri.', 'indikator' => ['kb' => 'Y', 'imunisasi' => 'Y', 'asi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Danu: belum ikut KB, punya bayi 2 bulan ASI eksklusif, imunisasi sudah lengkap, tidak ada yang merokok, belum punya BPJS, air sumur, ada jamban.', 'indikator' => ['kb' => 'T', 'asi' => 'Y', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Nani, tidak ikut program KB, anak pertama 9 tahun imunisasi lengkap, tidak ada lagi bayi, suami masih merokok, punya BPJS, air PDAM, tidak ada jamban (Buang air besar di sungai).', 'indikator' => ['kb' => 'T', 'imunisasi' => 'Y', 'rokok' => 'T', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'T']],
        ['catatan' => 'Keluarga Bapak Usman, istri pakai pil KB, bayi 4 bulan diberi ASI eksklusif dan rutin ke posyandu untuk ditimbang, tidak ada yang merokok, punya BPJS, sumber air sumur, jamban tercatat ada.', 'indikator' => ['kb' => 'Y', 'asi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Rudi, KB belum jalan, tidak ada bayi atau balita, anak sekolah imunisasinya lengkap dulu, masih ada yang merokok yaitu bapak, belum punya BPJS, air dari sungai, jamban tidak layak.', 'indikator' => ['kb' => 'T', 'imunisasi' => 'Y', 'rokok' => 'T', 'jkn' => 'T', 'air' => 'T', 'jamban' => 'T']],
        ['catatan' => 'Keluarga Bapak Slamet, istri ikut KB implan, bayi 6 bulan ASI saja, imunisasi lengkap, ditimbang di posyandu tiap bulan, tidak ada perokok, sudah terdaftar BPJS, air PDAM, jamban sendiri.', 'indikator' => ['kb' => 'Y', 'asi' => 'Y', 'imunisasi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Tono menderita darah tinggi dan berobat rutin minum obat ke puskesmas, istri KB suntik, anak 3 tahun ditimbang rutin, tidak ada yang merokok, punya BPJS, air sumur, jamban ada.', 'indikator' => ['hipertensi' => 'Y', 'kb' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Yanto: kakek menderita TBC tapi belum berobat, tidak ikut KB, tidak ada bayi, tidak ada yang merokok, belum punya BPJS, air sumur, jamban sendiri.', 'indikator' => ['tb' => 'T', 'kb' => 'T', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Siti, gangguan jiwa skizofrenia ditangani rutin di puskesmas, tidak punya KB, tidak ada anak kecil, ada yang merokok, punya BPJS, air PDAM, jamban sendiri.', 'indikator' => ['jiwa' => 'Y', 'kb' => 'T', 'rokok' => 'T', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Agus, biasa melahirkan di bidan, istri sudah KB, balita 2 tahun imunisasi lengkap tapi tidak pernah ditimbang, tidak ada perokok, BPJS ada, air sumur, jamban ada.', 'indikator' => ['bersalin' => 'Y', 'kb' => 'Y', 'imunisasi' => 'Y', 'balita' => 'T', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Budi, istri melahirkan di rumah ditolong dukun bayi, belum KB, bayi 3 bulan disusui tapi juga diberi susu formula, belum imunisasi lengkap, tidak ada yang merokok, belum BPJS, air sungai, jamban tidak ada.', 'indikator' => ['bersalin' => 'T', 'kb' => 'T', 'asi' => 'T', 'imunisasi' => 'T', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'T', 'jamban' => 'T']],
        ['catatan' => 'Keluarga Bapak Hasan, TB pengobatan rutin jalan, istri pakai KB pil, anak 8 tahun imunisasi lengkap, tidak ada balita, tidak ada yang merokok, terdaftar BPJS, air PDAM, jamban bersih.', 'indikator' => ['tb' => 'Y', 'kb' => 'Y', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Eko, hipertensi minum obat teratur, tidak ada KB karena sudah tidak ada pasangan muda, balita 4 tahun ditimbang di posyandu, masih ada perokok, BPJS terdaftar, air sumur, jamban ada.', 'indikator' => ['hipertensi' => 'Y', 'kb' => 'T', 'balita' => 'Y', 'rokok' => 'T', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Joko: istrinya belum ikut KB, tidak ada bayi, tidak ada yang merokok, sudah punya jaminan kesehatan BPJS, air dari pompa sumur, jamban tercatat layak.', 'indikator' => ['kb' => 'T', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Maya, suami perokok aktif, ikut KB suntik, anak 5 tahun imunisasi lengkap dan ditimbang, belum punya BPJS, air PDAM, jamban sendiri.', 'indikator' => ['kb' => 'Y', 'imunisasi' => 'Y', 'balita' => 'Y', 'rokok' => 'T', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Candra, tidak ada yang merokok, KB tidak jalan, bayi 1 bulan ASI eksklusif, imunisasi belum lengkap, tidak ditimbang, sudah BPJS, air sumur, jamban ada.', 'indikator' => ['kb' => 'T', 'asi' => 'Y', 'imunisasi' => 'T', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Firman jiwa skizofreni putus obat dan terlantar, istri KB pil, anak 7 tahun imunisasi lengkap, tidak ada yang merokok, belum BPJS, air PDAM, jamban sendiri.', 'indikator' => ['jiwa' => 'T', 'kb' => 'Y', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Guntur, istri melahirkan bantuan dokter di rumah sakit, KB belum, bayi 2 bulan ASI saja dan ditimbang di posyandu, tidak ada perokok, BPJS ada, air sumur, jamban ada.', 'indikator' => ['bersalin' => 'Y', 'kb' => 'T', 'asi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Ilham, semua anggota bebas rokok, istri KB implan, TBC pengobatan rutin di puskesmas, tidak ada balita, BPJS terdaftar, air PDAM, jamban sendiri.', 'indikator' => ['kb' => 'Y', 'tb' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Ratna, tidak ikut KB, anak 10 tahun imunisasi lengkap, tidak ada bayi, tidak ada yang merokok, belum punya BPJS, air kali tidak bersih, jamban tidak ada.', 'indikator' => ['kb' => 'T', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'T', 'jamban' => 'T']],
        ['catatan' => 'Keluarga Bapak Deddy, hipertensi darah tinggi belum berobat rutin, istri KB suntik, balita 3 tahun tidak pernah ditimbang, ada perokok, BPJS belum, air sumur, jamban ada.', 'indikator' => ['hipertensi' => 'T', 'kb' => 'Y', 'balita' => 'T', 'rokok' => 'T', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Farhan, tidak ada anggota dengan TB atau hipertensi, istri ikut KB, bayi 5 bulan ASI Eksklusif imunisasi lengkap ditimbang di posyandu, tidak ada yang merokok, BPJS ada, air PDAM, jamban.', 'indikator' => ['tb' => 'N', 'hipertensi' => 'N', 'kb' => 'Y', 'asi' => 'Y', 'imunisasi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Kardi, tidak ada yang merokok, belum KB, tidak ada bayi dan balita, anak 12 tahun imunisasi lengkap, sudah BPJS, air sumur, jamban sendiri.', 'indikator' => ['kb' => 'T', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Luki, belum ikut KB, bayi 1 bulan diberi susu formula bukan ASI, belum imunisasi, belum ditimbang, bapak merokok, belum BPJS, air sungai, jamban tidak ada.', 'indikator' => ['kb' => 'T', 'asi' => 'T', 'imunisasi' => 'T', 'rokok' => 'T', 'jkn' => 'T', 'air' => 'T', 'jamban' => 'T']],
        ['catatan' => 'Keluarga Bapak Munir: kakek gangguan jiwa dirawat di rumah sakit jiwa dan rutin minum obat, istri KB implan, tidak ada yang merokok, BPJS aktif, air PDAM, jamban sendiri.', 'indikator' => ['jiwa' => 'Y', 'kb' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Nurdin, TB tidak rutin minum obat, istri belum KB, tidak ada bayi, kepala keluarga merokok, BPJS ada, air sumur, jamban ada.', 'indikator' => ['tb' => 'T', 'kb' => 'T', 'rokok' => 'T', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Rina: melahirkan ditolong bidan di puskesmas, KB suntik, anak 2 tahun imunisasi lengkap dan ditimbang, tidak ada yang merokok, belum punya BPJS, air PDAM, jamban ada.', 'indikator' => ['bersalin' => 'Y', 'kb' => 'Y', 'imunisasi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Saeful, belum ada yang ikut KB, tidak ada bayi, tidak ada yang merokok, belum BPJS, air sumur banyak jamban sendiri.', 'indikator' => ['kb' => 'T', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Taufik: balita 3 tahun rutin ke posyandu dan ditimbang, istri KB injeksi, tidak ada perokok, sudah punya BPJS, air PDAM, jamban sendiri.', 'indikator' => ['kb' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Umar, belum ikut KB, bayi 3 bulan tidak diberi ASI tapi susu formula, belum imunisasi lengkap, belum ditimbang, ada perokok, punya BPJS, air sumur, jamban.', 'indikator' => ['kb' => 'T', 'asi' => 'T', 'imunisasi' => 'T', 'rokok' => 'T', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Wawan, tidak ada yang merokok, hipertensi teratur minum obat, istri sudah KB, balita 4 tahun ditimbang, terdaftar BPJS, air PDAM, jamban bersih.', 'indikator' => ['hipertensi' => 'Y', 'kb' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Zainal, tidak ada yang merokok, belum KB, tidak ada bayi, anak-anak sudah imunisasi lengkap, belum punya kartu sehat, air sumur, ada jamban.', 'indikator' => ['kb' => 'T', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Akmal, kakek TB berobat teratur, istri KB pil, bayi 6 bulan ASI eksklusif dan imunisasi lengkap, tidak ada perokok, BPJS ada, air PDAM, jamban.', 'indikator' => ['tb' => 'Y', 'kb' => 'Y', 'asi' => 'Y', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Yuli, belum ikut KB, tidak ada anak balita, tidak ada yang merokok, belum BPJS, air sumur, jamban ada.', 'indikator' => ['kb' => 'T', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Bahar, terduga skizofrenia ditangani rutin, istri ikut KB implan, tidak ada balita, tidak ada perokok, BPJS aktif, air PDAM, jamban.', 'indikator' => ['jiwa' => 'Y', 'kb' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Cipto, tidak ada yang merokok, belum KB namun sudah menikah usia muda, tidak ada bayi, sudah BPJS, air sumur, jamban sendiri.', 'indikator' => ['kb' => 'T', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Darmo, istri melahirkan di rumah ditolong paraji, belum KB, bayi 1 bulan disusui saja, imunisasi belum, belum ditimbang, tidak ada perokok, belum BPJS, air sungai, jamban tidak ada.', 'indikator' => ['bersalin' => 'T', 'kb' => 'T', 'asi' => 'Y', 'imunisasi' => 'T', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'T', 'jamban' => 'T']],
        ['catatan' => 'Keluarga Bapak Endang, hipertensi tidak minum obat rutin, istri KB suntik, anak 6 tahun imunisasi lengkap, tidak ada balita, ada perokok, BPJS ada, air PDAM, jamban.', 'indikator' => ['hipertensi' => 'T', 'kb' => 'Y', 'imunisasi' => 'Y', 'rokok' => 'T', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Fajar, tidak ada yang merokok bebaskan asap rokok di rumah, istri belum ikut KB, tidak ada bayi, sudah punya BPJS, air sumur, jamban sendiri.', 'indikator' => ['kb' => 'T', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Ibu Heni, balita 2 tahun KMS naik setiap bulan ditimbang di posyandu, istri KB, tidak ada perokok, belum BPJS, air sumur, jamban.', 'indikator' => ['kb' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Iwan, istri pakai IUD, bayi 4 bulan ASI eksklusif dan imunisasi lengkap, selalu dibawa ke posyandu, tidak ada perokok, sudah terdaftar BPJS, air PDAM, jamban bersih.', 'indikator' => ['kb' => 'Y', 'asi' => 'Y', 'imunisasi' => 'Y', 'balita' => 'Y', 'rokok' => 'Y', 'jkn' => 'Y', 'air' => 'Y', 'jamban' => 'Y']],
        ['catatan' => 'Keluarga Bapak Jaja, tidak ada yang merokok, belum KB, tidak ada bayi dan balita, imunisasi anak-anak lengkap, belum punya BPJS, air sumur, jamban ada.', 'indikator' => ['kb' => 'T', 'imunisasi' => 'Y', 'rokok' => 'Y', 'jkn' => 'T', 'air' => 'Y', 'jamban' => 'Y']],
    ];

    public function run(): void
    {
        AiTrainingLog::truncate();

        foreach (self::SAMPLES as $sample) {
            $indikator = array_merge(
                array_fill_keys(Indikator::ids(), 'N'),
                $sample['indikator']
            );

            AiTrainingLog::create([
                'catatan' => $sample['catatan'],
                'draft_json' => ['indikator' => $indikator],
                'final_json' => ['indikator' => $indikator],
                'created_by' => null,
                'created_at' => now(),
            ]);
        }

        $this->command?->info('Seeded '.count(self::SAMPLES).' catatan untuk ai:train.');
    }
}
