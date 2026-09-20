<?php

namespace App\Console\Commands;

use App\Models\Wilayah;
use App\Support\Indikator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportPwsCommand extends Command
{
    protected $signature = 'pws:import {path : Path file JSON hasil parse PWS}';

    protected $description = 'Import paksa + perbarui data keluarga dari file JSON hasil parse PWS.xlsx';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $families = json_decode(file_get_contents($path), true);

        if (! is_array($families) || $families === []) {
            $this->error('JSON kosong atau tidak valid.');

            return self::FAILURE;
        }

        $now = now()->toDateTimeString();
        $this->info('Memuat data keluarga yang sudah ada untuk deteksi duplikat...');

        $existing = DB::table('families')
            ->select('id', 'kepala_keluarga', 'desa', 'rw', 'rt')
            ->get()
            ->keyBy(fn ($row) => $this->familyKey($row->kepala_keluarga, $row->desa, $row->rw, $row->rt));

        $wilayahs = [];
        $membersBulk = [];
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($families, $existing, $now, &$wilayahs, &$membersBulk, &$created, &$updated) {
            foreach ($families as $family) {
                $key = $this->familyKey($family['kepala_keluarga'], $family['desa'], $family['rw'], $family['rt']);
                $wilayahs[$family['kecamatan'].'|'.$family['desa'].'|'.$family['rw'].'|'.$family['rt']] = true;

                $data = $this->familyColumns($family);
                $data['created_at'] = $now;
                $data['updated_at'] = $now;

                $memberRows = array_map(function (array $member) {
                    return [
                        'nama' => $member['nama'],
                        'umur' => $member['umur'] ? (string) $member['umur'] : null,
                        'jenis_kelamin' => $member['jenis_kelamin'] === 'P' ? 'P' : 'L',
                        'hubungan' => $member['hubungan'] ?: null,
                        'nik' => $member['nik'] ?: null,
                    ];
                }, $family['members'] ?? []);

                if (isset($existing[$key])) {
                    $id = $existing[$key]->id;
                    unset($data['created_at']);
                    DB::table('families')->where('id', $id)->update($data);
                    DB::table('family_members')->where('family_id', $id)->delete();
                    $updated++;

                    foreach ($memberRows as $member) {
                        $member['family_id'] = $id;
                        $member['created_at'] = $now;
                        $member['updated_at'] = $now;
                        $membersBulk[] = $member;
                    }

                    continue;
                }

                $id = DB::table('families')->insertGetId($data);
                $created++;

                foreach ($memberRows as $member) {
                    $member['family_id'] = $id;
                    $member['created_at'] = $now;
                    $member['updated_at'] = $now;
                    $membersBulk[] = $member;
                }
            }
        });

        $this->info('Menyisipkan '.count($membersBulk).' anggota keluarga...');
        foreach (array_chunk($membersBulk, 1000) as $chunk) {
            DB::table('family_members')->insert($chunk);
        }

        DB::transaction(function () use ($wilayahs) {
            foreach (array_keys($wilayahs) as $combo) {
                [$kecamatan, $desa, $rw, $rt] = explode('|', $combo);
                Wilayah::ensure($kecamatan ?: null, $desa ?: null, $rw ?: null, $rt ?: null);
            }
        });

        $this->info('Selesai. Dibuat: '.$created.', Diperbarui: '.$updated.', Anggota: '.count($membersBulk).', Wilayah: '.count($wilayahs).'.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $family
     * @return array<string, mixed>
     */
    private function familyColumns(array $family): array
    {
        $columns = [
            'no_kk' => $family['no_kk'] ?: null,
            'kepala_keluarga' => $family['kepala_keluarga'],
            'jalan' => $family['jalan'] ?: null,
            'rt' => $family['rt'] ?: null,
            'rw' => $family['rw'] ?: null,
            'desa' => $family['desa'] ?: null,
            'kecamatan' => $family['kecamatan'] ?: null,
            'surveyor' => $family['surveyor'] ?: null,
            'tanggal' => $family['tanggal'] ?: null,
        ];

        foreach (Indikator::ids() as $id) {
            $value = $family['ind_'.$id] ?? 'N';
            $columns['ind_'.$id] = in_array($value, ['Y', 'T', 'N'], true) ? $value : 'N';
        }

        return $columns;
    }

    private function familyKey(?string $nama, ?string $desa, ?string $rw, ?string $rt): string
    {
        return implode('|', [
            Str::lower(trim((string) $nama)),
            trim((string) $desa),
            trim((string) $rw),
            trim((string) $rt),
        ]);
    }
}
