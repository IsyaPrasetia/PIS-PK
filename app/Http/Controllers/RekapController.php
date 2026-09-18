<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RekapController extends Controller
{
    public function index(Request $request): View
    {
        $filterDesa = trim((string) $request->query('desa'));

        $all = Family::query()->scopedFor(Auth::user())->get();

        $desaOptions = $all
            ->map(fn (Family $family) => $family->desa ?: '(Tanpa Desa)')
            ->unique()
            ->sort()
            ->values();

        $families = $filterDesa === ''
            ? $all
            : $all->filter(fn (Family $family) => ($family->desa ?: '(Tanpa Desa)') === $filterDesa)->values();

        $tree = $this->buildTree($families);

        return view('rekap.index', [
            'tree' => $tree,
            'overall' => Family::iksStatsFor($families),
            'desaOptions' => $desaOptions,
            'filterDesa' => $filterDesa,
        ]);
    }

    /**
     * Susun data berjenjang Desa -> RW -> RT -> keluarga.
     */
    private function buildTree(iterable $families): array
    {
        $tree = [];

        foreach ($families as $family) {
            $desa = $family->desa ?: '(Tanpa Desa)';
            $rw = $family->rw ?: '(Tanpa RW)';
            $rt = $family->rt ?: '(Tanpa RT)';

            $tree[$desa][$rw][$rt][] = $family;
        }

        foreach ($tree as $desa => $rwMap) {
            foreach ($rwMap as $rw => $rtMap) {
                foreach ($rtMap as $rt => $items) {
                    $collection = collect($items)->sortBy('kepala_keluarga')->values()->all();
                    $tree[$desa][$rw][$rt] = $collection;
                }
            }
        }

        ksort($tree);

        return $tree;
    }
}
