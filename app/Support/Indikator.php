<?php

namespace App\Support;

final class Indikator
{
    public const DOMAIN_IBU_ANAK = 'KB & Kesehatan Ibu-Anak';

    public const DOMAIN_PENYAKIT = 'Pengendalian Penyakit';

    public const DOMAIN_PERILAKU = 'Perilaku & Lingkungan';

    public const JAWABAN = [
        'Y' => 'Ya',
        'T' => 'Tidak',
        'N' => 'Tidak berlaku',
    ];

    /**
     * Daftar 12 indikator PIS-PK, urut sesuai referensi resmi (berurutan 1-12).
     */
    public static function all(): array
    {
        return [
            ['id' => 'kb', 'domain' => self::DOMAIN_IBU_ANAK, 'q' => 'Keluarga mengikuti program Keluarga Berencana (KB)'],
            ['id' => 'bersalin', 'domain' => self::DOMAIN_IBU_ANAK, 'q' => 'Ibu melakukan persalinan di fasilitas kesehatan'],
            ['id' => 'imunisasi', 'domain' => self::DOMAIN_IBU_ANAK, 'q' => 'Bayi mendapat imunisasi dasar lengkap'],
            ['id' => 'asi', 'domain' => self::DOMAIN_IBU_ANAK, 'q' => 'Bayi mendapat Air Susu Ibu (ASI) eksklusif'],
            ['id' => 'balita', 'domain' => self::DOMAIN_IBU_ANAK, 'q' => 'Pertumbuhan balita dipantau tiap bulan'],
            ['id' => 'tb', 'domain' => self::DOMAIN_PENYAKIT, 'q' => 'Penderita tuberkulosis paru berobat sesuai standar'],
            ['id' => 'hipertensi', 'domain' => self::DOMAIN_PENYAKIT, 'q' => 'Penderita hipertensi melakukan pengobatan secara teratur'],
            ['id' => 'jiwa', 'domain' => self::DOMAIN_PENYAKIT, 'q' => 'Penderita gangguan jiwa berat diobati dan tidak ditelantarkan'],
            ['id' => 'rokok', 'domain' => self::DOMAIN_PERILAKU, 'q' => 'Anggota keluarga tidak ada yang merokok'],
            ['id' => 'jkn', 'domain' => self::DOMAIN_PERILAKU, 'q' => 'Keluarga sudah menjadi anggota Jaminan Kesehatan Nasional (JKN)'],
            ['id' => 'air', 'domain' => self::DOMAIN_PERILAKU, 'q' => 'Keluarga mempunyai akses / menggunakan sarana air bersih'],
            ['id' => 'jamban', 'domain' => self::DOMAIN_PERILAKU, 'q' => 'Keluarga mempunyai akses / menggunakan jamban sehat'],
        ];
    }

    /**
     * Urutan huruf/nomor indikator di tabel referensi (berjenjang per domain).
     */
    public static function number(string $id): int
    {
        return array_search($id, self::ids(), true) + 1;
    }

    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    public static function domains(): array
    {
        return [
            self::DOMAIN_IBU_ANAK,
            self::DOMAIN_PENYAKIT,
            self::DOMAIN_PERILAKU,
        ];
    }

    public static function forDomain(string $domain): array
    {
        return array_values(array_filter(self::all(), fn ($i) => $i['domain'] === $domain));
    }
}
