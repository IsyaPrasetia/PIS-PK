<?php

namespace App\Http\Controllers;

use App\Http\Requests\FamilyRequest;
use App\Models\AiTrainingLog;
use App\Models\Family;
use App\Support\Indikator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->query('q');

        $families = Family::query()
            ->scopedFor(Auth::user())
            ->search($term)
            ->withCount('members')
            ->orderByDesc('updated_at')
            ->get();

        return view('families.index', [
            'families' => $families,
            'term' => (string) $term,
        ]);
    }

    public function create(): View
    {
        $draft = session()->pull('ai_draft');

        $user = Auth::user();

        if ($draft !== null) {
            $family = new Family($this->mergeScope($user, [
                'kepala_keluarga' => $draft['kepala_keluarga'] ?? '',
                'no_kk' => $draft['no_kk'] ?? '',
                'jalan' => $draft['jalan'] ?? '',
                'rt' => $draft['rt'] ?? '',
                'rw' => $draft['rw'] ?? '',
                'desa' => $draft['desa'] ?? '',
                'kecamatan' => $draft['kecamatan'] ?? '',
                'catatan' => $draft['catatan'] ?? '',
            ]));

            return view('families.form', [
                'family' => $family,
                'members' => $draft['anggota'] ?: [$this->blankMember()],
                'indicatorValues' => array_merge(array_fill_keys(Indikator::ids(), 'N'), $draft['indikator'] ?? []),
                'flagged' => $draft['flagged'] ?? [],
                'isEdit' => false,
                'fromAi' => true,
                'aiNote' => $draft['catatan'] ?? '',
                'aiDraft' => $draft,
                'user' => $user,
            ]);
        }

        return view('families.form', [
            'family' => $this->prefillFor($user),
            'members' => [$this->blankMember()],
            'indicatorValues' => array_fill_keys(Indikator::ids(), 'N'),
            'flagged' => [],
            'isEdit' => false,
            'fromAi' => false,
            'user' => $user,
        ]);
    }

    public function store(FamilyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $members = $data['members'];
        unset($data['members']);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $family = Family::create($data);
        $family->members()->createMany($members);

        if (filled($request->input('ai_note'))) {
            $this->logTraining($request, $family, $members);
        }

        return redirect()
            ->route('families.index')
            ->with('success', 'Data keluarga tersimpan.');
    }

    /**
     * Simpan pasangan (catatan asli → data final yang disimpan) sebagai dataset training.
     *
     * @param  array<int, array<string, string>>  $members
     */
    private function logTraining(Request $request, Family $family, array $members): void
    {
        $final = [
            'kepala_keluarga' => $family->kepala_keluarga,
            'no_kk' => $family->no_kk,
            'jalan' => $family->jalan,
            'rt' => $family->rt,
            'rw' => $family->rw,
            'desa' => $family->desa,
            'kecamatan' => $family->kecamatan,
            'catatan' => $family->catatan,
            'anggota' => $members,
            'indikator' => $family->indicatorValues(),
        ];

        AiTrainingLog::create([
            'catatan' => $request->input('ai_note'),
            'draft_json' => json_decode($request->input('ai_draft', '') ?: '{}', true) ?: [],
            'final_json' => $final,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    public function edit(Family $family): View
    {
        $this->authorizeFamily($family, edit: true);

        $family->load('members');

        $members = $family->members->isNotEmpty()
            ? $family->members->map(fn ($member) => [
                'nama' => $member->nama,
                'umur' => $member->umur,
                'jenis_kelamin' => $member->jenis_kelamin,
                'hubungan' => $member->hubungan,
                'nik' => $member->nik,
            ])->all()
            : [$this->blankMember()];

        return view('families.form', [
            'family' => $family,
            'members' => $members,
            'indicatorValues' => $family->indicatorValues(),
            'flagged' => [],
            'isEdit' => true,
            'fromAi' => false,
            'user' => Auth::user(),
        ]);
    }

    public function update(FamilyRequest $request, Family $family): RedirectResponse
    {
        $this->authorizeFamily($family, edit: true);

        $data = $request->validated();
        $members = $data['members'];
        unset($data['members']);
        $data['updated_by'] = Auth::id();

        $family->update($data);
        $family->members()->delete();
        $family->members()->createMany($members);

        return redirect()
            ->route('families.index')
            ->with('success', 'Data keluarga tersimpan.');
    }

    public function destroy(Family $family): RedirectResponse
    {
        $this->authorizeFamily($family, edit: true);

        $family->delete();

        return redirect()
            ->route('families.index')
            ->with('success', 'Data keluarga dihapus.');
    }

    /**
     * @return array{nama: string, umur: string, jenis_kelamin: string, hubungan: string, nik: string}
     */
    private function blankMember(): array
    {
        return ['nama' => '', 'umur' => '', 'jenis_kelamin' => 'L', 'hubungan' => 'Kepala Keluarga', 'nik' => ''];
    }

    /**
     * Prefill scope wilayah pengguna saat form baru agar data langsung masuk wilayahnya.
     */
    private function prefillFor($user): Family
    {
        return new Family($this->mergeScope($user, ['kepala_keluarga' => '']));
    }

    /**
     * Isi kolom wilayah dari scope user jika draf AI belum menyertakannya.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeScope($user, array $data): array
    {
        foreach (['kecamatan', 'desa', 'rw', 'rt'] as $field) {
            if (empty($data[$field]) && ($user->{$field} ?? null)) {
                $data[$field] = $user->{$field};
            }
        }

        return $data;
    }

    private function authorizeFamily(Family $family, bool $edit = false): void
    {
        $user = Auth::user();

        if ($edit) {
            abort_unless($user->canEditFamily($family), 403, 'Anda tidak berwenang mengubah data ini.');
        } else {
            abort_unless($user->canViewFamily($family), 403, 'Data ini di luar wilayah Anda.');
        }
    }
}
