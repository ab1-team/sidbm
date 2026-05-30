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
        if (request()->user()) {
            $suffix = request()->user()->lokasi;
            config(['tenant.suffix' => "_" . $suffix]);

            return $next($request);
        }

        $domain = $request->getHost();

        $tenant = Kecamatan::where('web_kec', $domain)->orwhere('web_alternatif', $domain)->first();
        $suffix = $tenant ? "_{$tenant->id}" : '_1';

        config(['tenant.suffix' => $suffix]);

        if (!$tenant) {
            config(['database.default' => 'mysql_b']);
            DB::purge('mysql_b');
            DB::reconnect('mysql_b');
        } elseif (str_contains($tenant->web_kec, '.id') || str_contains((string) $tenant->web_alternatif, '.id')) {
            config(['database.default' => 'mysql_b']);
            DB::purge('mysql_b');
            DB::reconnect('mysql_b');
        }

        return $next($request);
    }
}
