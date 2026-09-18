<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WilayahController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->canManageUsers(), 403);

        $rows = Wilayah::query()
            ->when(! Auth::user()->isSuperadmin(), fn ($query) => $query->where('kecamatan', Auth::user()->kecamatan))
            ->orderBy('kecamatan')
            ->orderBy('desa')
            ->orderByRaw('CAST(rw AS UNSIGNED)')
            ->orderByRaw('CAST(rt AS UNSIGNED)')
            ->get();

        $tree = [];

        foreach ($rows as $row) {
            $kecamatan = (string) $row->kecamatan;
            $desa = (string) ($row->desa ?? '');
            $rw = (string) ($row->rw ?? '');
            $rt = (string) ($row->rt ?? '');

            $tree[$kecamatan] ??= [];
            if ($desa === '') {
                continue;
            }

            $tree[$kecamatan][$desa] ??= [];
            if ($rw === '') {
                continue;
            }

            $tree[$kecamatan][$desa][$rw] ??= [];
            if ($rt !== '') {
                $tree[$kecamatan][$desa][$rw][] = $rt;
            }
        }

        $kecamatans = array_keys($tree);
        sort($kecamatans);

        return view('wilayah.index', [
            'tree' => $tree,
            'kecamatans' => $kecamatans,
            'isSuperadmin' => Auth::user()->isSuperadmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(Auth::user()->canManageUsers(), 403);

        $level = (string) $request->input('level');
        $isSuperadmin = Auth::user()->isSuperadmin();

        $rules = [
            'level' => ['required', Rule::in(['kecamatan', 'desa', 'rw', 'rt'])],
            'nama' => ['required', 'string', 'max:255'],
            'kecamatan' => [$isSuperadmin ? 'required' : 'nullable', 'string', 'max:255'],
            'desa' => [in_array($level, ['rw', 'rt'], true) ? 'required' : 'nullable', 'string', 'max:255'],
            'rw' => [$level === 'rt' ? 'required' : 'nullable', 'string', 'max:10'],
        ];

        $data = $request->validate($rules);
        $nama = trim($data['nama']);

        if ($level === 'kecamatan') {
            abort_unless($isSuperadmin, 403);
            Wilayah::ensure($nama);
            $message = 'Kecamatan "'.$nama.'" ditambahkan.';
        } else {
            $kecamatan = $isSuperadmin ? trim((string) ($data['kecamatan'] ?? '')) : (string) Auth::user()->kecamatan;

            if ($level === 'desa') {
                Wilayah::ensure($kecamatan, $nama);
                $message = 'Desa/Kelurahan "'.$nama.'" ditambahkan ke '.$kecamatan.'.';
            } elseif ($level === 'rw') {
                $desa = trim((string) $data['desa']);
                Wilayah::ensure($kecamatan, $desa, $nama);
                $message = 'RW "'.$nama.'" ditambahkan ke '.$kecamatan.' / '.$desa.'.';
            } else {
                $desa = trim((string) $data['desa']);
                $rw = trim((string) $data['rw']);
                Wilayah::ensure($kecamatan, $desa, $rw, $nama);
                $message = 'RT "'.$nama.'" ditambahkan ke '.$kecamatan.' / '.$desa.' / RW '.$rw.'.';
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route('wilayah.index')
            ->with('success', $message);
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->canManageUsers(), 403);

        $data = $request->validate([
            'level' => ['required', Rule::in(['kecamatan', 'desa', 'rw', 'rt'])],
            'kecamatan' => ['required', 'string', 'max:255'],
            'desa' => ['nullable', 'string', 'max:255'],
            'rw' => ['nullable', 'string', 'max:10'],
            'rt' => ['nullable', 'string', 'max:10'],
        ]);

        if (! Auth::user()->isSuperadmin() && $data['kecamatan'] !== Auth::user()->kecamatan) {
            abort(403);
        }

        $query = Wilayah::query()->where('kecamatan', $data['kecamatan']);

        if ($data['level'] !== 'kecamatan') {
            $query->where('desa', $data['desa'] ?? null);
        }

        if (in_array($data['level'], ['rw', 'rt'], true)) {
            $query->where('rw', $data['rw'] ?? null);
        }

        if ($data['level'] === 'rt') {
            $query->where('rt', $data['rt'] ?? null);
        }

        $query->delete();

        return redirect()
            ->route('wilayah.index')
            ->with('success', 'Wilayah dihapus dari master data.');
    }
}
