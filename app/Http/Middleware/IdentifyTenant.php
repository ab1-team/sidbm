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

        $tenantFromB = DB::connection('mysql_b')
            ->table('kecamatan')
            ->where('web_kec', $domainId)
            ->orWhere('web_alternatif', $domainId)
            ->first();

        if ($tenantFromB) {
            config(['database.default' => 'mysql_b']);
        }

        if (request()->user()) {
            $suffix = request()->user()->lokasi;
            config(['tenant.suffix' => "_" . $suffix]);

            return $next($request);
        }

        if ($tenantFromB) {
            $tenant = Kecamatan::on('mysql_b')->find($tenantFromB->id);
        } else {
            $tenant = Kecamatan::where('web_kec', $domain)->orWhere('web_alternatif', $domain)->first();
        }

        $suffix = $tenant ? "_{$tenant->id}" : '_1';
        config(['tenant.suffix' => $suffix]);

        return $next($request);
    }
}
