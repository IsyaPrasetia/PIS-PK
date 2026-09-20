<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Family;
use App\Models\User;
use App\Support\Indikator;
use App\Support\Segmentasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_segmentasi_page_loads_for_admin(): void
    {
        $this->actingAs($this->superadmin())
            ->get('/segmentasi')
            ->assertOk()
            ->assertSee('Segmentasi Keluarga');
    }

    public function test_petugas_cannot_access_segmentasi(): void
    {
        $this->actingAs($this->user())
            ->get('/segmentasi')
            ->assertForbidden();
    }

    public function test_keluarga_sehat_masuk_segmen_sehat_stabil_dan_data_lengkap(): void
    {
        $family = $this->family([
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '03',
            ...$this->indicatorInputs('Y'),
        ]);

        $analisis = Segmentasi::analisis($family);

        $this->assertContains('data_lengkap', $analisis['segments']);
        $this->assertContains('sehat_stabil', $analisis['segments']);
        $this->assertSame(0, $analisis['priority_score']);
        $this->assertSame('rendah', $analisis['priority_level']);
        $this->assertFalse($analisis['requires_human_review']);
    }

    public function test_masalah_penyakit_kronis_diberi_segmen(): void
    {
        $inputs = $this->indicatorInputs('Y');
        $inputs['ind_tb'] = 'T';
        $inputs['ind_hipertensi'] = 'T';

        $analisis = Segmentasi::analisis($this->family($inputs));

        $this->assertContains('penyakit_kronis', $analisis['segments']);
        $this->assertContains('Penderita hipertensi belum melakukan pengobatan secara teratur', $analisis['reasons']);
    }

    public function test_kebutuhan_ibu_dan_anak_diberi_segmen(): void
    {
        $inputs = $this->indicatorInputs('Y');
        $inputs['ind_imunisasi'] = 'T';
        $inputs['ind_balita'] = 'T';

        $analisis = Segmentasi::analisis($this->family($inputs));

        $this->assertContains('ibu_anak', $analisis['segments']);
        $this->assertContains('Pertumbuhan balita belum dipantau tiap bulan', $analisis['reasons']);
    }

    public function test_sanitasi_dan_perlindungan_sosial_diberi_segmen(): void
    {
        $inputs = $this->indicatorInputs('Y');
        $inputs['ind_air'] = 'T';
        $inputs['ind_jamban'] = 'T';
        $inputs['ind_rokok'] = 'T';
        $inputs['ind_jkn'] = 'T';

        $analisis = Segmentasi::analisis($this->family($inputs));

        $this->assertContains('sanitasi', $analisis['segments']);
        $this->assertContains('perlindungan_sosial', $analisis['segments']);
        $this->assertContains('Keluarga belum menjadi peserta JKN', $analisis['reasons']);
    }

    public function test_data_kosong_memicu_perlu_verifikasi_bukan_tidak_sehat(): void
    {
        $family = $this->family(['ind_kb' => 'Y']);

        $analisis = Segmentasi::analisis($family);

        $this->assertContains('data_perlu_verifikasi', $analisis['segments']);
        $this->assertNotEquals('Tidak Sehat', $analisis['kategori_iks']);
        $this->assertTrue($analisis['requires_human_review']);
    }

    public function test_duplikasi_potensial_dideteksi(): void
    {
        $first = $this->family(['kepala_keluarga' => 'Sutrisno', 'kecamatan' => 'Cileunyi', 'desa' => 'Cibiru', 'rw' => '05', 'rt' => '03']);
        $second = $this->family(['kepala_keluarga' => '  sutrisno ', 'kecamatan' => 'Cileunyi', 'desa' => 'Cibiru', 'rw' => '05', 'rt' => '03']);

        $ids = Segmentasi::idDuplikatPotensial(collect([$first, $second]));

        $this->assertArrayHasKey($first->id, $ids);
        $this->assertArrayHasKey($second->id, $ids);

        $analisis = Segmentasi::analisis($first, ['is_duplikat' => true]);
        $this->assertContains('duplikasi_potensial', $analisis['segments']);
        $this->assertTrue($analisis['requires_human_review']);
    }

    public function test_data_kadaluarsa_memicu_segmen_dan_tinjauan(): void
    {
        $family = $this->family($this->indicatorInputs('Y'));
        $family->forceFill(['updated_at' => now()->subMonths(8)])->save();

        $analisis = Segmentasi::analisis($family->fresh());

        $this->assertContains('data_kadaluarsa', $analisis['segments']);
        $this->assertTrue($analisis['requires_human_review']);
    }

    public function test_skor_prioritas_mengikuti_bobot_masalah(): void
    {
        $inputs = $this->indicatorInputs('T');
        $inputs['ind_rokok'] = 'T';
        $inputs['ind_jkn'] = 'T';
        $inputs['ind_air'] = 'T';
        $inputs['ind_jamban'] = 'T';

        $analisis = Segmentasi::analisis($this->family($inputs));

        $this->assertSame('sangat_tinggi', $analisis['priority_level']);
        $this->assertGreaterThanOrEqual(80, $analisis['priority_score']);
        $this->assertTrue($analisis['requires_human_review']);
    }

    private function family(array $data): Family
    {
        return Family::create([
            'kepala_keluarga' => 'Uji',
            ...$data,
        ]);
    }

    private function superadmin(): User
    {
        return User::factory()->create(['role' => UserRole::Superadmin->value]);
    }

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Petugas->value]);
    }

    /**
     * @return array<string, string>
     */
    private function indicatorInputs(string $value): array
    {
        $inputs = [];

        foreach (Indikator::ids() as $id) {
            $inputs['ind_'.$id] = $value;
        }

        return $inputs;
    }
}
