<?php

namespace Database\Seeders;

use App\Models\Family;
use Illuminate\Database\Seeder;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->families() as $data) {
            $members = $data['anggota'];
            $indicators = $data['indikator'];
            unset($data['anggota'], $data['indikator']);

            $family = Family::create($data);
            $family->members()->createMany($members);
            $family->update($indicators);
        }
    }

    /**
     * Indikator default: semua "Ya" lalu ditimpa sesuai kondisi keluarga.
     */
    private function indikator(array $overrides = []): array
    {
        $base = array_fill_keys([
            'ind_kb', 'ind_bersalin', 'ind_imunisasi', 'ind_asi', 'ind_balita',
            'ind_tb', 'ind_hipertensi', 'ind_jiwa', 'ind_rokok', 'ind_jkn',
            'ind_air', 'ind_jamban',
        ], 'Y');

        return array_merge($base, $overrides);
    }

    private function families(): array
    {
        return [
            [
                'no_kk' => '3204012501190001',
                'kepala_keluarga' => 'Sutrisno',
                'jalan' => 'Jl. Anggrek No. 12',
                'rt' => '03', 'rw' => '05', 'desa' => 'Cibiru', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Rina', 'tanggal' => '2026-09-10',
                'catatan' => 'Keluarga aktif ke posyandu, rumah memiliki jamban sendiri.',
                'anggota' => [
                    ['nama' => 'Sutrisno', 'umur' => '42', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190001'],
                    ['nama' => 'Wati', 'umur' => '38', 'jenis_kelamin' => 'P', 'hubungan' => 'Istri', 'nik' => '3204010101190002'],
                    ['nama' => 'Dimas', 'umur' => '10', 'jenis_kelamin' => 'L', 'hubungan' => 'Anak', 'nik' => '3204010101190003'],
                    ['nama' => 'Nadia', 'umur' => '3', 'jenis_kelamin' => 'P', 'hubungan' => 'Anak', 'nik' => '3204010101190004'],
                ],
                'indikator' => $this->indikator(),
            ],
            [
                'no_kk' => '3204012501190002',
                'kepala_keluarga' => 'Ahmad Fauzi',
                'jalan' => 'Jl. Anggrek No. 20',
                'rt' => '03', 'rw' => '05', 'desa' => 'Cibiru', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Rina', 'tanggal' => '2026-09-10',
                'catatan' => 'Ada anggota keluarga yang merokok.',
                'anggota' => [
                    ['nama' => 'Ahmad Fauzi', 'umur' => '50', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190011'],
                    ['nama' => 'Lilis', 'umur' => '46', 'jenis_kelamin' => 'P', 'hubungan' => 'Istri', 'nik' => '3204010101190012'],
                ],
                'indikator' => $this->indikator([
                    'ind_kb' => 'N', 'ind_bersalin' => 'N', 'ind_imunisasi' => 'N',
                    'ind_asi' => 'N', 'ind_balita' => 'N', 'ind_rokok' => 'T',
                ]),
            ],
            [
                'no_kk' => '3204012501190003',
                'kepala_keluarga' => 'Siti Aminah',
                'jalan' => 'Kp. Babakan No. 4',
                'rt' => '04', 'rw' => '05', 'desa' => 'Cibiru', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Dedi', 'tanggal' => '2026-09-11',
                'catatan' => 'Belum memiliki jamban sehat, memakai MCK umum.',
                'anggota' => [
                    ['nama' => 'Siti Aminah', 'umur' => '35', 'jenis_kelamin' => 'P', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190021'],
                    ['nama' => 'Rian', 'umur' => '8', 'jenis_kelamin' => 'L', 'hubungan' => 'Anak', 'nik' => '3204010101190022'],
                ],
                'indikator' => $this->indikator([
                    'ind_kb' => 'T', 'ind_bersalin' => 'N', 'ind_imunisasi' => 'T',
                    'ind_asi' => 'N', 'ind_balita' => 'T', 'ind_hipertensi' => 'T',
                    'ind_rokok' => 'T', 'ind_jkn' => 'T', 'ind_air' => 'T', 'ind_jamban' => 'T',
                ]),
            ],
            [
                'no_kk' => '3204012501190004',
                'kepala_keluarga' => 'Budi Santoso',
                'jalan' => 'Jl. Melati No. 7',
                'rt' => '01', 'rw' => '02', 'desa' => 'Cibiru', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Rina', 'tanggal' => '2026-09-12',
                'catatan' => 'Semua indikator terpenuhi.',
                'anggota' => [
                    ['nama' => 'Budi Santoso', 'umur' => '40', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190031'],
                    ['nama' => 'Yuni', 'umur' => '37', 'jenis_kelamin' => 'P', 'hubungan' => 'Istri', 'nik' => '3204010101190032'],
                    ['nama' => 'Alya', 'umur' => '2', 'jenis_kelamin' => 'P', 'hubungan' => 'Anak', 'nik' => '3204010101190033'],
                ],
                'indikator' => $this->indikator(),
            ],
            [
                'no_kk' => '3204012501190005',
                'kepala_keluarga' => 'Dewi Lestari',
                'jalan' => 'Jl. Melati No. 15',
                'rt' => '01', 'rw' => '02', 'desa' => 'Cibiru', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Dedi', 'tanggal' => '2026-09-12',
                'catatan' => 'Anggota keluarga ada yang menderita hipertensi.',
                'anggota' => [
                    ['nama' => 'Dewi Lestari', 'umur' => '55', 'jenis_kelamin' => 'P', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190041'],
                    ['nama' => 'Hendra', 'umur' => '29', 'jenis_kelamin' => 'L', 'hubungan' => 'Anak', 'nik' => '3204010101190042'],
                ],
                'indikator' => $this->indikator([
                    'ind_kb' => 'N', 'ind_bersalin' => 'N', 'ind_imunisasi' => 'N',
                    'ind_asi' => 'N', 'ind_balita' => 'N', 'ind_hipertensi' => 'T',
                ]),
            ],
            [
                'no_kk' => '3204012501190006',
                'kepala_keluarga' => 'Rahmat Hidayat',
                'jalan' => 'Kp. Cileunyi No. 3',
                'rt' => '02', 'rw' => '01', 'desa' => 'Cileunyi Wetan', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Sari', 'tanggal' => '2026-09-13',
                'catatan' => 'Keluarga memiliki bayi yang rutin ditimbang.',
                'anggota' => [
                    ['nama' => 'Rahmat Hidayat', 'umur' => '33', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190051'],
                    ['nama' => 'Intan', 'umur' => '30', 'jenis_kelamin' => 'P', 'hubungan' => 'Istri', 'nik' => '3204010101190052'],
                    ['nama' => 'Bayu', 'umur' => '1', 'jenis_kelamin' => 'L', 'hubungan' => 'Anak', 'nik' => '3204010101190053'],
                ],
                'indikator' => $this->indikator([
                    'ind_tb' => 'N', 'ind_hipertensi' => 'N', 'ind_jiwa' => 'N',
                ]),
            ],
            [
                'no_kk' => '3204012501190007',
                'kepala_keluarga' => 'Nurhayati',
                'jalan' => 'Kp. Cileunyi No. 11',
                'rt' => '02', 'rw' => '01', 'desa' => 'Cileunyi Wetan', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Sari', 'tanggal' => '2026-09-13',
                'catatan' => 'Ibu hamil, belum terdaftar JKN.',
                'anggota' => [
                    ['nama' => 'Nurhayati', 'umur' => '28', 'jenis_kelamin' => 'P', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190061'],
                    ['nama' => 'Joko', 'umur' => '31', 'jenis_kelamin' => 'L', 'hubungan' => 'Suami', 'nik' => '3204010101190062'],
                ],
                'indikator' => $this->indikator([
                    'ind_kb' => 'T', 'ind_bersalin' => 'T', 'ind_imunisasi' => 'N',
                    'ind_asi' => 'N', 'ind_balita' => 'N', 'ind_tb' => 'N',
                    'ind_hipertensi' => 'N', 'ind_jiwa' => 'N', 'ind_jkn' => 'T',
                ]),
            ],
            [
                'no_kk' => '3204012501190008',
                'kepala_keluarga' => 'Agus Setiawan',
                'jalan' => 'Kp. Cileunyi No. 25',
                'rt' => '05', 'rw' => '03', 'desa' => 'Cileunyi Wetan', 'kecamatan' => 'Cileunyi',
                'surveyor' => 'Kader Sari', 'tanggal' => '2026-09-14',
                'catatan' => 'Ada anggota dengan gangguan jiwa yang rutin berobat.',
                'anggota' => [
                    ['nama' => 'Agus Setiawan', 'umur' => '48', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga', 'nik' => '3204010101190071'],
                    ['nama' => 'Sari', 'umur' => '45', 'jenis_kelamin' => 'P', 'hubungan' => 'Istri', 'nik' => '3204010101190072'],
                    ['nama' => 'Tono', 'umur' => '22', 'jenis_kelamin' => 'L', 'hubungan' => 'Anak', 'nik' => '3204010101190073'],
                ],
                'indikator' => $this->indikator([
                    'ind_kb' => 'N', 'ind_bersalin' => 'N', 'ind_imunisasi' => 'N',
                    'ind_asi' => 'N', 'ind_balita' => 'N', 'ind_rokok' => 'T',
                ]),
            ],
        ];
    }
}
