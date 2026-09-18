<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Family;
use App\Models\User;
use App\Support\Indikator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_pages_load(): void
    {
        $this->actingAs($this->superadmin());

        foreach (['/', '/keluarga', '/rekap', '/indikator', '/impor', '/ai', '/keluarga/tambah'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_flow_with_password(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AdminWilayah->value,
            'password' => 'rahasia123',
        ]);

        $this->get('/login')->assertOk();

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_dashboard_shows_wilayah_recap_and_chart(): void
    {
        $this->actingAs($this->superadmin());

        $this->family([
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '03',
            ...$this->indicatorInputs('Y'),
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-wilayah-level="kecamatan"', false)
            ->assertSee('data-wilayah-level="desa"', false)
            ->assertSee('data-wilayah-level="rw"', false)
            ->assertSee('data-wilayah-level="rt"', false)
            ->assertSee('id="wilayah-chart"', false)
            ->assertSee('id="wilayah-data"', false)
            ->assertSee('data-indikator-mode="batang"', false)
            ->assertSee('data-indikator-mode="kartesius"', false)
            ->assertSee('id="indikator-chart"', false)
            ->assertSee('id="indikator-data"', false)
            ->assertSee('Cileunyi');
    }

    public function test_assets_use_the_request_host_through_a_tunnel(): void
    {
        $this->actingAs($this->superadmin());

        $response = $this->withServerVariables([
            'HTTP_X_FORWARDED_HOST' => 'jim-rabbit-hill-angel.trycloudflare.com',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/');

        $response->assertOk()
            ->assertSee('https://jim-rabbit-hill-angel.trycloudflare.com/build/', false);
    }

    public function test_a_family_can_be_created(): void
    {
        $this->actingAs($this->superadmin());

        $response = $this->post('/keluarga', $this->payload());

        $response->assertRedirect(route('families.index'));

        $this->assertDatabaseHas('families', [
            'kepala_keluarga' => 'Sutrisno',
            'ind_kb' => 'Y',
            'ind_rokok' => 'T',
        ]);

        $this->assertDatabaseHas('family_members', ['nama' => 'Sutrisno']);
    }

    public function test_kepala_keluarga_is_required(): void
    {
        $this->actingAs($this->superadmin());

        $payload = $this->payload();
        unset($payload['kepala_keluarga']);

        $this->post('/keluarga', $payload)->assertSessionHasErrors('kepala_keluarga');
    }

    public function test_iks_classification(): void
    {
        $sehat = $this->family($this->indicatorInputs('Y'));
        $this->assertSame('sehat', $sehat->iks()['class']);
        $this->assertSame(1.0, $sehat->iks()['score']);

        $overrides = $this->indicatorInputs('Y');
        $overrides['ind_kb'] = 'T';
        $overrides['ind_rokok'] = 'T';
        $overrides['ind_jkn'] = 'T';
        $overrides['ind_air'] = 'T';
        $pra = $this->family($overrides);
        $this->assertSame('pra', $pra->iks()['class']);

        $tidak = $this->family($this->indicatorInputs('T'));
        $this->assertSame('tidak', $tidak->iks()['class']);
    }

    public function test_rows_can_be_imported(): void
    {
        $this->actingAs($this->superadmin());

        $response = $this->postJson('/impor', [
            'rows' => [[
                'kepala_keluarga' => 'Impor Satu',
                'desa' => 'Cibiru',
                'indikator' => ['kb' => 'Y', 'rokok' => 'T'],
                'anggota' => [['nama' => 'Anak A', 'umur' => '20', 'jenis_kelamin' => 'P']],
            ]],
        ]);

        $response->assertOk()->assertJson(['saved' => 1]);
        $this->assertDatabaseHas('families', ['kepala_keluarga' => 'Impor Satu', 'ind_rokok' => 'T']);
        $this->assertDatabaseHas('family_members', ['nama' => 'Anak A']);
    }

    public function test_petugas_only_sees_families_in_their_scope(): void
    {
        $petugas = $this->user([
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '03',
        ]);

        $this->family([
            'kepala_keluarga' => 'Dalam Wilayah',
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '03',
            ...$this->indicatorInputs('Y'),
        ]);
        $this->family([
            'kepala_keluarga' => 'Luar Wilayah',
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '99',
            ...$this->indicatorInputs('Y'),
        ]);

        $petugas = User::find($petugas->id) ?? $petugas;
        $response = $this->actingAs($petugas)->get('/keluarga');

        $response->assertOk()
            ->assertSee('Dalam Wilayah')
            ->assertDontSee('Luar Wilayah');
    }

    public function test_petugas_can_only_edit_own_family_within_24_hours(): void
    {
        $petugas = $this->user();
        $own = Family::create([
            'kepala_keluarga' => 'Milik Petugas',
            'created_by' => $petugas->id,
            ...$this->indicatorInputs('Y'),
        ]);
        $own->forceFill(['created_at' => now()->subHours(2)])->save();

        $old = Family::create([
            'kepala_keluarga' => 'Data Lama Petugas',
            'created_by' => $petugas->id,
            ...$this->indicatorInputs('Y'),
        ]);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->actingAs($petugas);

        $this->get('/keluarga/'.$own->id.'/ubah')->assertOk();
        $this->get('/keluarga/'.$old->id.'/ubah')->assertForbidden();
    }

    public function test_petugas_cannot_edit_others_family_even_recent(): void
    {
        $petugas = $this->user();
        $others = Family::create([
            'kepala_keluarga' => 'Milik Kader',
            'created_by' => $this->superadmin()->id,
            'created_at' => now(),
            ...$this->indicatorInputs('Y'),
        ]);

        $this->actingAs($petugas)->get('/keluarga/'.$others->id.'/ubah')->assertForbidden();
    }

    public function test_non_super_admin_cannot_manage_users(): void
    {
        $this->actingAs($this->user(['role' => UserRole::AdminRW->value]))
            ->get('/pengguna')
            ->assertForbidden();

        $this->actingAs($this->user(['role' => UserRole::AdminRT->value]))
            ->get('/pengguna/tambah')
            ->assertForbidden();
    }

    public function test_admin_wilayah_can_manage_roles_below_it(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::AdminWilayah->value,
            'kecamatan' => 'Cileunyi',
        ]);

        $response = $this->actingAs($admin)->get('/pengguna');
        $response->assertOk()->assertSee('Manajemen Pengguna');

        $response = $this->actingAs($admin)->post('/pengguna', [
            'name' => 'Petugas Baru',
            'email' => 'baru@pispk.test',
            'password' => 'rahasia123',
            'role' => UserRole::Petugas->value,
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '03',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'baru@pispk.test', 'role' => UserRole::Petugas->value]);
    }

    public function test_super_admin_can_create_accounts(): void
    {
        $this->actingAs($this->superadmin());

        $response = $this->post('/pengguna', [
            'name' => 'Admin Wilayah Baru',
            'email' => 'wilayah2@pispk.test',
            'password' => 'rahasia123',
            'role' => UserRole::AdminWilayah->value,
            'kecamatan' => 'Bandung',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'wilayah2@pispk.test', 'role' => UserRole::AdminWilayah->value]);
    }

    public function test_dashboard_can_be_filtered_by_region_and_domain(): void
    {
        $this->actingAs($this->superadmin());

        $this->family([
            'kepala_keluarga' => 'Keluarga Cibiru',
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cibiru',
            'rw' => '05',
            'rt' => '03',
            ...$this->indicatorInputs('Y'),
        ]);
        $this->family([
            'kepala_keluarga' => 'Keluarga Cileunyi Wetan',
            'kecamatan' => 'Cileunyi',
            'desa' => 'Cileunyi Wetan',
            'rw' => '01',
            'rt' => '02',
            'ind_rokok' => 'T',
            ...array_diff_key($this->indicatorInputs('Y'), ['ind_rokok' => true]),
        ]);

        $this->get('/?kecamatan=Cileunyi')->assertOk();
        $this->get('/?kecamatan=Cileunyi&desa=Cibiru')->assertOk();
        $this->get('/?kecamatan=Cileunyi&desa=Cibiru&rw=05&rt=03')->assertOk();

        $this->get('/?domain='.urlencode(Indikator::DOMAIN_IBU_ANAK))
            ->assertOk()
            ->assertSee('Keluarga Berencana');

        $this->get('/?kecamatan=Cileunyi&desa=Cileunyi%20Wetan&domain='.urlencode(Indikator::DOMAIN_PERILAKU))
            ->assertOk();
    }

    public function test_ai_extract_builds_draft_and_stores_training_log(): void
    {
        $this->actingAs($this->superadmin());

        $note = 'Keluarga Bapak Sutrisno di Jl. Anggrek RT 03 RW 05 Desa Cibiru. '
            .'Istrinya bernama Wati, 34 tahun, ikut KB suntik. '
            .'Tidak ada yang merokok. Punya BPJS, air dari PDAM, ada jamban.';

        $response = $this->post('/ai/ekstrak', ['catatan' => $note]);

        $response->assertRedirect(route('families.create'));
        $response->assertSessionHas('ai_draft');

        $payload = $this->payload();
        $payload['ai_note'] = $note;
        $payload['ai_draft'] = json_encode([
            'kepala_keluarga' => 'Sutrisno',
            'indikator' => ['kb' => 'Y', 'rokok' => 'Y'],
        ]);

        $this->post('/keluarga', $payload)->assertRedirect(route('families.index'));

        $this->assertDatabaseHas('ai_training_logs', ['catatan' => $note]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function user(array $overrides = []): User
    {
        return User::factory()->create($overrides + ['role' => UserRole::Petugas->value]);
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'kepala_keluarga' => 'Sutrisno',
            'desa' => 'Cibiru',
            'members' => [
                ['nama' => 'Sutrisno', 'umur' => '42', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga'],
            ],
            ...$this->indicatorInputs('Y'),
            'ind_rokok' => 'T',
        ];
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
