<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Wilayah;
use App\Support\Indikator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImporController extends Controller
{
    public function index(): View
    {
        return view('impor.index', [
            'indikator' => Indikator::all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.kepala_keluarga' => ['required', 'string', 'max:255'],
            'rows.*.no_kk' => ['nullable', 'string', 'max:32'],
            'rows.*.jalan' => ['nullable', 'string', 'max:255'],
            'rows.*.rt' => ['nullable', 'string', 'max:10'],
            'rows.*.rw' => ['nullable', 'string', 'max:10'],
            'rows.*.desa' => ['nullable', 'string', 'max:255'],
            'rows.*.kecamatan' => ['nullable', 'string', 'max:255'],
            'rows.*.surveyor' => ['nullable', 'string', 'max:255'],
            'rows.*.tanggal' => ['nullable', 'date'],
            'rows.*.catatan' => ['nullable', 'string'],
            'rows.*.indikator' => ['nullable', 'array'],
            'rows.*.anggota' => ['nullable', 'array'],
        ]);

        $saved = 0;
        $rejected = 0;
        $user = Auth::user();

        DB::transaction(function () use ($validated, $user, &$saved, &$rejected) {
            foreach ($validated['rows'] as $row) {
                $data = [
                    'no_kk' => $row['no_kk'] ?? null,
                    'kepala_keluarga' => $row['kepala_keluarga'],
                    'jalan' => $row['jalan'] ?? null,
                    'rt' => $row['rt'] ?? null,
                    'rw' => $row['rw'] ?? null,
                    'desa' => $row['desa'] ?? null,
                    'kecamatan' => $row['kecamatan'] ?? null,
                    'surveyor' => $row['surveyor'] ?? null,
                    'tanggal' => $row['tanggal'] ?? null,
                    'catatan' => $row['catatan'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    ...$this->indikatorColumns($row['indikator'] ?? []),
                ];

                $candidate = new Family($data);

                if (! $user->canViewFamily($candidate)) {
                    $rejected++;

                    continue;
                }

                $family = Family::create($data);

                Wilayah::ensure($data['kecamatan'], $data['desa'], $data['rw'], $data['rt']);

                $members = collect($row['anggota'] ?? [])
                    ->filter(fn (array $member) => trim((string) ($member['nama'] ?? '')) !== '')
                    ->map(fn (array $member) => [
                        'nama' => $member['nama'],
                        'umur' => $member['umur'] ?? null,
                        'jenis_kelamin' => ($member['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L',
                        'hubungan' => $member['hubungan'] ?? null,
                        'nik' => $member['nik'] ?? null,
                    ])
                    ->values()
                    ->all();

                if ($members !== []) {
                    $family->members()->createMany($members);
                }

                $saved++;
            }
        });

        return response()->json([
            'saved' => $saved,
            'rejected' => $rejected,
            'message' => $saved.' data keluarga berhasil diimpor.'
                .($rejected > 0 ? ' '.$rejected.' baris diabaikan karena di luar wilayah Anda.' : ''),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function indikatorColumns(array $values): array
    {
        $columns = [];

        foreach (Indikator::ids() as $id) {
            $value = $values[$id] ?? 'N';
            $columns['ind_'.$id] = in_array($value, ['Y', 'T', 'N'], true) ? $value : 'N';
        }

        return $columns;
    }
}
