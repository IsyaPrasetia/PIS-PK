<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceRequestRootUrl
{
    /**
     * Samakan root semua URL (aset, route, redirect) dengan host/skema
     * permintaan aktif, agar aplikasi tetap benar saat diakses lewat
     * tunnel/domain berbeda (mis. cloudflared).
     */
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
