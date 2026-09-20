<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Wilayah;
use App\Support\Segmentasi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SegmentationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        abort_unless($user->isAdmin(), 403);

        $filters = [
            'kecamatan' => trim((string) $request->query('kecamatan')),
            'desa' => trim((string) $request->query('desa')),
            'rw' => trim((string) $request->query('rw')),
            'rt' => trim((string) $request->query('rt')),
        ];

        $base = Family::query()
            ->scopedFor($user)
            ->withCount('members')
            ->latest('updated_at');

        foreach (['kecamatan', 'desa', 'rw', 'rt'] as $column) {
            if ($filters[$column] !== '') {
                $base->where($column, $filters[$column]);
            }
        }

        $families = $base->get();
        $duplikatIds = Segmentasi::idDuplikatPotensial($families);

        $rows = $families->map(fn (Family $family) => [
            'family' => $family,
            'analisis' => Segmentasi::analisis($family, [
                'is_duplikat' => isset($duplikatIds[$family->id]),
            ]),
        ]);

        // Tata urut: skor prioritas turun, lalu untuk IKS sama skor, yang paling buruk di atas.
        $rows = $rows->sortBy([
            ['analisis.priority_score', 'desc'],
            ['analisis.iks', 'asc'],
        ])->values();

        $segmenTerpilih = trim((string) $request->query('segmen'));

        if ($segmenTerpilih !== '' && isset(Segmentasi::katalog()[$segmenTerpilih])) {
            $rows = $rows->filter(
                fn (array $row) => in_array($segmenTerpilih, $row['analisis']['segments'], true)
            )->values();
        }

        $rekap = $this->rekap($rows);

        $perPage = (int) $request->query('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $paginator = new LengthAwarePaginator(
            $rows->slice(0, $perPage),
            $rows->count(),
            $perPage,
            $request->integer('page', 1),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('segmentasi.index', [
            'rows' => $paginator,
            'rekap' => $rekap,
            'filters' => $filters,
            'segmenTerpilih' => $segmenTerpilih,
            'perPage' => $perPage,
            'wilayahTree' => $this->wilayahTree($user),
            'katalog' => Segmentasi::katalog(),
            'perKelompok' => Segmentasi::perKelompok(),
            'total' => $families->count(),
        ]);
    }

    /**
     * Ringkasan segmentasi: jumlah per segmen, per level prioritas, dan sebaran per kelurahan.
     *
     * @param  Collection<int, array{family: Family, analisis: array<string, mixed>}>  $rows
     * @return array<string, mixed>
     */
    private function rekap($rows): array
    {
        $perSegmen = array_fill_keys(array_keys(Segmentasi::katalog()), 0);
        $perLevel = ['rendah' => 0, 'sedang' => 0, 'tinggi' => 0, 'sangat_tinggi' => 0];
        $perluTinjau = 0;
        $duplikat = 0;

        foreach ($rows as $row) {
            $analisis = $row['analisis'];

            foreach ($analisis['segments'] as $id) {
                $perSegmen[$id]++;
            }

            $perLevel[$analisis['priority_level']]++;
            $perluTinjau += $analisis['requires_human_review'] ? 1 : 0;
            $duplikat += in_array('duplikasi_potensial', $analisis['segments'], true) ? 1 : 0;
        }

        $wilayah = [];

        foreach ($rows as $row) {
            $family = $row['family'];
            $analisis = $row['analisis'];
            $label = ($family->kecamatan ?: '(Tanpa Kecamatan)').' '.(($family->desa ?: '(Tanpa Desa)') ?: '');
            $wilayah[$label]['n'] = ($wilayah[$label]['n'] ?? 0) + 1;
            $wilayah[$label]['skor'] = ($wilayah[$label]['skor'] ?? 0) + $analisis['priority_score'];
            $wilayah[$label]['perlu_tinjau'] = ($wilayah[$label]['perlu_tinjau'] ?? 0) + ($analisis['requires_human_review'] ? 1 : 0);

            foreach ($analisis['segments'] as $id) {
                $wilayah[$label]['seg'][$id] = ($wilayah[$label]['seg'][$id] ?? 0) + 1;
            }
        }

        $wilayahList = [];

        foreach ($wilayah as $label => $data) {
            $wilayahList[] = [
                'label' => $label,
                'n' => $data['n'],
                'avg_priority' => $data['n'] > 0 ? (int) round($data['skor'] / $data['n']) : 0,
                'perlu_tinjau' => $data['perlu_tinjau'],
                'seg' => $data['seg'] ?? [],
            ];
        }

        usort($wilayahList, fn (array $a, array $b) => strcmp($a['label'], $b['label']));

        return [
            'n' => $rows->count(),
            'perSegmen' => $perSegmen,
            'perLevel' => $perLevel,
            'perluTinjau' => $perluTinjau,
            'duplikat' => $duplikat,
            'wilayah' => $wilayahList,
        ];
    }

    /**
     * Struktur hierarki wilayah untuk filter berjenjang, dari master data, dibatasi scope pengguna.
     *
     * @return array<string, array<string, array<string, list<string>>>>
     */
    private function wilayahTree($user): array
    {
        $kecamatans = Wilayah::kecamatans();

        if (! $user->isSuperadmin()) {
            $kecamatans = array_values(array_filter($kecamatans, fn ($kecamatan) => $kecamatan === $user->kecamatan));
        }

        $tree = [];

        foreach ($kecamatans as $kecamatan) {
            $tree[$kecamatan] = [];

            $desas = Wilayah::desas($kecamatan);

            if (! $user->isSuperadmin() && $user->desa) {
                $desas = array_values(array_filter($desas, fn ($desa) => $desa === $user->desa));
            }

            foreach ($desas as $desa) {
                $tree[$kecamatan][$desa] = [];

                $rws = Wilayah::rws($kecamatan, $desa);

                if (! $user->isSuperadmin() && $user->rw) {
                    $rws = array_values(array_filter($rws, fn ($rw) => $rw === $user->rw));
                }

                foreach ($rws as $rw) {
                    $rts = Wilayah::rts($kecamatan, $desa, $rw);

                    if (! $user->isSuperadmin() && $user->rt) {
                        $rts = array_values(array_filter($rts, fn ($rt) => $rt === $user->rt));
                    }

                    $tree[$kecamatan][$desa][$rw] = $rts;
                }
            }
        }

        return $tree;
    }
}
