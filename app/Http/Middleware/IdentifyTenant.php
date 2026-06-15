<?php

namespace App\Http\Middleware;

use App\Models\Kecamatan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->getHost();
        $domainId = str_replace('.net', '.id', $domain);

        // 1. Cek di mysql_b (holding) — kecamatan dulu
        $tenantFromB = DB::connection('mysql_b')
            ->table('kecamatan')
            ->where('web_kec', $domainId)
            ->orWhere('web_alternatif', $domainId)
            ->first();

        // 2. Kalau bukan kecamatan, cek kabupaten di mysql_b
        $kabFromB = null;
        if (! $tenantFromB) {
            $kabFromB = DB::connection('mysql_b')
                ->table('kabupaten')
                ->where('web_kab', $domainId)
                ->orWhere('web_kab_alternatif', $domainId)
                ->first();
        }

        // 3. Domain milik tenant (kecamatan ATAU kabupaten) → pakai holding DB
        if ($tenantFromB || $kabFromB) {
            config(['database.default' => 'mysql_b']);
        }

        // 4. Set flag untuk downstream (controller / view) bahwa ini request kabupaten
        config(['tenant.is_kab' => (bool) $kabFromB]);

        if (request()->user()) {
            $suffix = request()->user()->lokasi;
            config(['tenant.suffix' => "_" . $suffix]);

            return $next($request);
        }

        if ($tenantFromB) {
            $tenant = Kecamatan::on('mysql_b')->find($tenantFromB->id);
        } elseif ($kabFromB) {
            // Domain kabupaten — suffix pakai id_kab agar konsistensi dengan tabel pinjaman_kab_*
            $tenant = (object) ['id' => $kabFromB->id];
        } else {
            // Fallback: cek default DB (untuk domain lokal/development)
            $tenant = Kecamatan::where('web_kec', $domain)->orWhere('web_alternatif', $domain)->first();
        }

        $suffix = $tenant ? "_{$tenant->id}" : '_1';
        config(['tenant.suffix' => $suffix]);

        return $next($request);
    }
}
