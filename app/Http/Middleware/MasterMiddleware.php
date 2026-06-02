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
        if (auth()->guard('master')->check()) {
            if (auth()->guard('master')->user()->akses == 'master') {
                $server = session('master_server', 'mysql_b'); // Default ke server B
                config(['database.default' => $server]);
                
                return $next($request);
            }
        }

        return redirect('/master');
    }
}
