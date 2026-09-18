<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        if (Wilayah::exists()) {
            return;
        }

        $data = [
            'Cileunyi' => [
                'Cibiru' => ['01' => ['01', '02', '03'], '02' => ['01', '02'], '05' => ['03', '04']],
                'Cileunyi Wetan' => ['01' => ['02', '05'], '03' => ['01', '02', '05']],
            ],
            'Cileunyi Kulon' => [
                'Cileunyi Kulon' => ['01' => ['01', '02', '03'], '02' => ['01', '02']],
            ],
            'Cimenyan' => [
                'Cimenyan' => ['01' => ['01', '02'], '02' => ['01', '02', '03']],
                'Mekarsaluyu' => ['01' => ['01', '02']],
            ],
            'Cilengkrang' => [
                'Cilengkrang' => ['01' => ['01', '02', '03'], '02' => ['01', '02']],
            ],
        ];

        $rows = [];

        foreach ($data as $kecamatan => $desas) {
            foreach ($desas as $desa => $rws) {
                foreach ($rws as $rw => $rts) {
                    foreach ($rts as $rt) {
                        $rows[] = [
                            'kecamatan' => $kecamatan,
                            'desa' => $desa,
                            'rw' => $rw,
                            'rt' => $rt,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        Wilayah::insert($rows);
    }
}
