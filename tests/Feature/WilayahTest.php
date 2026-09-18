<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_manager_cannot_access_master_wilayah(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas->value]);

        $this->actingAs($petugas)->get('/wilayah')->assertForbidden();
        $this->actingAs($petugas)->post('/wilayah', ['level' => 'desa', 'nama' => 'X'])->assertForbidden();
    }

    public function test_superadmin_can_manage_master_wilayah(): void
    {
        $this->actingAs($this->superadmin());

        $this->get('/wilayah')->assertOk()->assertSee('Master Wilayah');

        $this->post('/wilayah', [
            'level' => 'desa',
            'kecamatan' => 'Cileunyi',
            'nama' => 'Cibiru Hilir',
        ])->assertRedirect(route('wilayah.index'));

        $this->assertDatabaseHas('wilayah', [
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru Hilir',
        ]);

        $this->post('/wilayah', [
            'level' => 'rw',
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru Hilir',
            'nama' => '07',
        ])->assertRedirect(route('wilayah.index'));

        $this->post('/wilayah', [
            'level' => 'rt',
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru Hilir',
            'rw' => '07',
            'nama' => '03',
        ])->assertRedirect(route('wilayah.index'));

        $this->assertContains('Cibiru Hilir', Wilayah::desas('Cileunyi'));
        $this->assertContains('07', Wilayah::rws('Cileunyi', 'Cibiru Hilir'));
        $this->assertContains('03', Wilayah::rts('Cileunyi', 'Cibiru Hilir', '07'));
    }

    public function test_adding_same_wilayah_is_idempotent(): void
    {
        $this->actingAs($this->superadmin());

        $payload = ['level' => 'desa', 'kecamatan' => 'Cileunyi', 'nama' => 'Cibiru Hilir'];

        $this->post('/wilayah', $payload);
        $this->post('/wilayah', $payload);

        $this->assertSame(1, Wilayah::query()
            ->where('kecamatan', 'Cileunyi')
            ->where('desa', 'Cibiru Hilir')
            ->whereNull('rw')
            ->whereNull('rt')
            ->count());
    }

    public function test_admin_wilayah_only_manages_own_kecamatan(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::AdminWilayah->value,
            'kecamatan' => 'Cileunyi',
        ]);

        $this->actingAs($admin)->post('/wilayah', [
            'level' => 'desa',
            'nama' => 'Desa Baru',
        ])->assertRedirect(route('wilayah.index'));

        $this->assertDatabaseHas('wilayah', [
            'kecamatan' => 'Cileunyi',
            'desa' => 'Desa Baru',
        ]);

        $this->actingAs($admin)->post('/wilayah', ['level' => 'kecamatan', 'nama' => 'Kecamatan Baru'])
            ->assertForbidden();
    }

    public function test_excel_import_registers_wilayah_automatically(): void
    {
        $this->actingAs($this->superadmin());

        $response = $this->postJson('/impor', [
            'rows' => [[
                'kepala_keluarga' => 'Keluarga Impor',
                'kecamatan' => 'Cileunyi',
                'desa' => 'Cibiru Hilir',
                'rw' => '09',
                'rt' => '04',
                'indikator' => [],
                'anggota' => [],
            ]],
        ]);

        $response->assertOk()->assertJson(['saved' => 1]);

        $this->assertDatabaseHas('wilayah', [
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru Hilir',
            'rw' => '09',
            'rt' => '04',
        ]);
    }

    private function superadmin(): User
    {
        return User::factory()->create(['role' => UserRole::Superadmin->value]);
    }
}
