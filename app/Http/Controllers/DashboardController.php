<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Wilayah;
use App\Support\Indikator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $filters = [
            'kecamatan' => trim((string) $request->query('kecamatan')),
            'desa' => trim((string) $request->query('desa')),
            'rw' => trim((string) $request->query('rw')),
            'rt' => trim((string) $request->query('rt')),
            'domain' => trim((string) $request->query('domain')),
        ];

        $base = Family::query()
            ->scopedFor($user)
            ->withCount('members')
            ->latest('updated_at');

        $baseFamilies = (clone $base)->get();
        $wilayahTree = $this->wilayahTree($user);

        $region = array_filter([
            'kecamatan' => $filters['kecamatan'],
            'desa' => $filters['desa'],
            'rw' => $filters['rw'],
            'rt' => $filters['rt'],
        ], fn ($value) => $value !== '');

        foreach ($region as $column => $value) {
            $base->where($column, $value);
        }

        $families = $base->get();
        $stats = Family::iksStatsFor($families);

        $indicators = $this->indicatorStats($families, $filters['domain']);
        $indikatorChart = $this->indicatorChart($families, $filters['domain']);

        return view('dashboard', [
            'families' => $families,
            'stats' => $stats,
            'indicators' => $indicators,
            'indikatorChart' => $indikatorChart,
            'wilayah' => $this->wilayahRecap($families),
            'filters' => $filters,
            'wilayahTree' => $wilayahTree,
            'hasRegionFilter' => $region !== [],
        ]);
    }

    /**
     * Statistik per indikator ("Ya" dari yang berlaku), opsional dibatasi domain.
     *
     * @param  Collection<int, Family>  $families
     * @param  string  $domain  Domain indikator atau '' untuk semua.
     * @return array<int, array<string, mixed>>
     */
    private function indicatorStats($families, string $domain = ''): array
    {
        $indicators = $domain === '' ? Indikator::all() : Indikator::forDomain($domain);

        return collect($indicators)->map(function (array $indicator) use ($families) {
            $ya = $tidak = 0;

            foreach ($families as $family) {
                $value = $family->{'ind_'.$indicator['id']};

                if ($value === 'Y') {
                    $ya++;
                } elseif ($value === 'T') {
                    $tidak++;
                }
            }

            $answered = $ya + $tidak;

            return $indicator + [
                'ya' => $ya,
                'tidak' => $tidak,
                'pct' => $answered > 0 ? $ya / $answered : null,
            ];
        })->all();
    }

    /**
     * Data untuk grafik kartesius per indikator.
     *
     * @param  Collection<int, Family>  $families
     * @return Collection<int, array<string, mixed>>
     */
    private function indicatorChart($families, string $domain = ''): Collection
    {
        $indicators = $domain === '' ? Indikator::all() : Indikator::forDomain($domain);

        return collect($indicators)->map(function (array $indicator) use ($families) {
            $ya = $tidak = 0;

            foreach ($families as $family) {
                $value = $family->{'ind_'.$indicator['id']};

                if ($value === 'Y') {
                    $ya++;
                } elseif ($value === 'T') {
                    $tidak++;
                }
            }

            $answered = $ya + $tidak;

            return [
                'id' => $indicator['id'],
                'q' => $indicator['q'],
                'pct' => $answered > 0 ? round($ya / $answered, 4) : null,
                'ya' => $ya,
                'tidak' => $tidak,
            ];
        })->values();
    }

    /**
     * Struktur hierarki wilayah untuk filter berjenjang, diambil dari master data
     * (bukan dari keluarga) dan dibatasi scope pengguna aktif.
     *
     * @return array<string, array<string, array<string, list<string>>>>
     */
    private function wilayahTree($user): array
    {
        $kecamatans = Wilayah::kecamatans();

        if (! $user->isSuperadmin()) {
            $kecamatans = array_values(array_filter(
                $kecamatans,
                fn ($kecamatan) => $kecamatan === $user->kecamatan
            ));
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

    /**
     * Rekap IKS berkelompok per jenjang wilayah (kecamatan, desa/kelurahan, RW, RT).
     *
     * @param  Collection<int, Family>  $families
     * @return array<string, array{label: string, rows: array<int, array<string, mixed>>}>
     */
    private function wilayahRecap($families): array
    {
        $levels = [
            'kecamatan' => [
                'label' => 'Kecamatan',
                'key' => fn (Family $family) => $family->kecamatan ?: '(Tanpa Kecamatan)',
            ],
            'desa' => [
                'label' => 'Kelurahan/Desa',
                'key' => function (Family $family) {
                    $kecamatan = $family->kecamatan ?: '(Tanpa Kecamatan)';

                    return $kecamatan.' · '.($family->desa ?: '(Tanpa Desa)');
                },
            ],
            'rw' => [
                'label' => 'RW',
                'key' => function (Family $family) {
                    $desa = $family->desa ?: '(Tanpa Desa)';

                    return $desa.' · RW '.($family->rw ?: '—');
                },
            ],
            'rt' => [
                'label' => 'RT',
                'key' => function (Family $family) {
                    $desa = $family->desa ?: '(Tanpa Desa)';

                    return $desa.' · RW '.($family->rw ?: '—').' · RT '.($family->rt ?: '—');
                },
            ],
        ];

        $recap = [];

        foreach ($levels as $level => $config) {
            $rows = [];

            foreach ($families->groupBy($config['key']) as $label => $items) {
                $stats = Family::iksStatsFor($items);

                $rows[] = [
                    'label' => (string) $label,
                    'n' => $stats['n'],
                    'avg' => $stats['avg'],
                    'sehat' => $stats['sehat'],
                    'pra' => $stats['pra'],
                    'tidak' => $stats['tidak'],
                ];
            }

            usort($rows, fn (array $a, array $b) => strcmp($a['label'], $b['label']));

            $recap[$level] = ['label' => $config['label'], 'rows' => $rows];
        }

        return $recap;
    }
}
