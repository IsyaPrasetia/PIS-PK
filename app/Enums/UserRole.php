<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case AdminWilayah = 'admin_wilayah';
    case AdminRW = 'admin_rw';
    case AdminRT = 'admin_rt';
    case Petugas = 'petugas';

    /**
     * Label yang ditampilkan ke pengguna.
     */
    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::AdminWilayah => 'Admin Wilayah',
            self::AdminRW => 'Admin RW',
            self::AdminRT => 'Admin RT',
            self::Petugas => 'Petugas Lapangan',
        };
    }

    /**
     * Prioritas hierarki. Superadmin paling tinggi (0), petugas paling rendah (4).
     */
    public function level(): int
    {
        return match ($this) {
            self::Superadmin => 0,
            self::AdminWilayah => 1,
            self::AdminRW => 2,
            self::AdminRT => 3,
            self::Petugas => 4,
        };
    }

    /**
     * Semua role tersedia sebagai opsi.
     *
     * @return array<string, UserRole>
     */
    public static function options(): array
    {
        return [
            self::Superadmin->value => self::Superadmin,
            self::AdminWilayah->value => self::AdminWilayah,
            self::AdminRW->value => self::AdminRW,
            self::AdminRT->value => self::AdminRT,
            self::Petugas->value => self::Petugas,
        ];
    }
}
