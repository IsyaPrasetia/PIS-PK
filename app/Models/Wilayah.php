<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kecamatan', 'desa', 'rw', 'rt'])]
class Wilayah extends Model
{
    protected $table = 'wilayah';

    private const KECAMATAN_DEFAULT = ['Cileunyi', 'Cileunyi Kulon', 'Cimenyan', 'Cilengkrang'];

    /**
     * Daftar kecamatan yang tersedia di master data.
     *
     * @return list<string>
     */
    public static function kecamatans(): array
    {
        $values = self::query()
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan')
            ->map(fn ($value) => (string) $value)
            ->all();

        return $values !== [] ? $values : self::KECAMATAN_DEFAULT;
    }

    /**
     * Desa/kelurahan dalam satu kecamatan.
     *
     * @return list<string>
     */
    public static function desas(string $kecamatan): array
    {
        return self::query()
            ->where('kecamatan', $kecamatan)
            ->whereNotNull('desa')
            ->where('desa', '!=', '')
            ->distinct()
            ->orderBy('desa')
            ->pluck('desa')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * Nomor RW dalam satu kecamatan + desa.
     *
     * @return list<string>
     */
    public static function rws(string $kecamatan, string $desa): array
    {
        return self::query()
            ->where('kecamatan', $kecamatan)
            ->where('desa', $desa)
            ->whereNotNull('rw')
            ->where('rw', '!=', '')
            ->distinct()
            ->orderByRaw('CAST(rw AS UNSIGNED)')
            ->pluck('rw')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * Nomor RT dalam satu kecamatan + desa + RW.
     *
     * @return list<string>
     */
    public static function rts(string $kecamatan, string $desa, string $rw): array
    {
        return self::query()
            ->where('kecamatan', $kecamatan)
            ->where('desa', $desa)
            ->where('rw', $rw)
            ->whereNotNull('rt')
            ->where('rt', '!=', '')
            ->distinct()
            ->orderByRaw('CAST(rt AS UNSIGNED)')
            ->pluck('rt')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * Pastikan kombinasi wilayah terdaftar di master data (tidak dobel).
     */
    public static function ensure(?string $kecamatan, ?string $desa = null, ?string $rw = null, ?string $rt = null): self
    {
        $kecamatan = self::clean($kecamatan);

        if ($kecamatan === null) {
            return new self(['kecamatan' => '']);
        }

        return self::firstOrCreate([
            'kecamatan' => $kecamatan,
            'desa' => self::clean($desa),
            'rw' => self::clean($rw),
            'rt' => self::clean($rt),
        ]);
    }

    /**
     * Bersihkan nilai opsional; '' dan kosong menjadi null.
     */
    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
