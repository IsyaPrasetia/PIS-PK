<?php

namespace App\Http\Controllers;

use App\Support\Indikator;
use Illuminate\View\View;

class IndikatorController extends Controller
{
    public function index(): View
    {
        return view('indikator.index', [
            'domains' => collect(Indikator::domains())->mapWithKeys(
                fn (string $domain) => [$domain => Indikator::forDomain($domain)]
            ),
        ]);
    }
}
