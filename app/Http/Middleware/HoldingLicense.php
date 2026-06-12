<?php

namespace App\Http\Middleware;

use App\Models\Kecamatan;
use App\Models\License;
use Closure;
use Illuminate\Http\Request;

class HoldingLicense
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Holding-Token');
        $slug  = $request->header('X-Holding-Tenant');

        // Tenant = Kecamatan. Slug = web_kec atau web_alternatif.
        $license = License::where('api_secret', $token)
            ->where('is_active', true)
            ->whereHas('kecamatan', function ($q) use ($slug) {
                $q->where('web_kec', $slug)
                  ->orWhere('web_alternatif', $slug);
            })
            ->first();

        abort_unless($license, 401, 'Token tidak valid.');
        abort_if($license->isExpired(), 403, 'Lisensi kedaluwarsa.');

        // Set tenant (kecamatan) + suffix agar Keuangan & model lain filter per-kec.
        $kecamatan = $license->kecamatan;
        config(['tenant.suffix' => '_' . $kecamatan->id]);

        $request->attributes->set('holding_license', $license);
        $request->attributes->set('holding_kecamatan', $kecamatan);

        return $next($request);
    }
}
