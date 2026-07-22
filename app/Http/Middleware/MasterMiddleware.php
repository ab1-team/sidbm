<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Swap koneksi DULU supaya SubstituteBindings (yang jalan setelah middleware ini
        // di group 'master', tapi SEBELUM untuk global web) pakai DB yang benar.
        // Lihat Kernel::$middlewarePriority — middleware ini diprioritaskan sebelum SubstituteBindings.
        if (auth()->guard('master')->check()) {
            $server = session('master_server', 'mysql_b');
            config(['database.default' => $server]);
        }

        if (auth()->guard('master')->check() && auth()->guard('master')->user()->akses == 'master') {
            return $next($request);
        }

        return redirect('/master');
    }
}
