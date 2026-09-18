<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@pispk.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('rahasia123'),
                'role' => UserRole::Superadmin->value,
            ],
        );

        $adminWilayah = User::firstOrCreate(
            ['email' => 'wilayah@pispk.test'],
            [
                'name' => 'Admin Wilayah Cileunyi',
                'password' => bcrypt('rahasia123'),
                'role' => UserRole::AdminWilayah->value,
                'kecamatan' => 'Cileunyi',
                'created_by' => $superadmin->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'rw@pispk.test'],
            [
                'name' => 'Admin RW 05 Cibiru',
                'password' => bcrypt('rahasia123'),
                'role' => UserRole::AdminRW->value,
                'kecamatan' => 'Cileunyi',
                'desa' => 'Cibiru',
                'rw' => '05',
                'created_by' => $adminWilayah->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'rt@pispk.test'],
            [
                'name' => 'Admin RT 03 Cibiru',
                'password' => bcrypt('rahasia123'),
                'role' => UserRole::AdminRT->value,
                'kecamatan' => 'Cileunyi',
                'desa' => 'Cibiru',
                'rw' => '05',
                'rt' => '03',
                'created_by' => $adminWilayah->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'petugas@pispk.test'],
            [
                'name' => 'Petugas Rina',
                'password' => bcrypt('rahasia123'),
                'role' => UserRole::Petugas->value,
                'kecamatan' => 'Cileunyi',
                'desa' => 'Cibiru',
                'rw' => '05',
                'rt' => '03',
                'created_by' => $adminWilayah->id,
            ],
        );
    }
}
