<?php

namespace App\Http\Middleware;

use App\Models\AdminInvoice;
use App\Models\Kecamatan;
use App\Support\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $domain = strtolower(trim($request->getHost()));
        $resolved = TenantResolver::resolveByDomain($domain);

        // Aktifkan koneksi holding hanya jika tenant ditemukan di DB holding.
        if ($resolved) {
            TenantResolver::applyResolvedConnection($resolved);
        }

        TenantResolver::markAsKabupaten(($resolved['type'] ?? null) === 'kabupaten');

        if ($user = request()->user()) {
            $suffix = $user->lokasi;
            config(['tenant.suffix' => "_" . $suffix]);

            // Cek pemblokiran jika user memiliki tagihan invoice unpaid yang overdue
            if ($user->uname !== 'superadmin') {
                $invoice = AdminInvoice::on('mysql')->where([
                    ['lokasi', $user->lokasi],
                    ['status', 'UNPAID'],
                ])->orderBy('tgl_invoice', 'ASC')->first();

                if (! $invoice) {
                    $invoice = AdminInvoice::on('mysql_b')->where([
                        ['lokasi', $user->lokasi],
                        ['status', 'UNPAID'],
                    ])->orderBy('tgl_invoice', 'ASC')->first();
                }

                if ($invoice) {
                    $kec = Kecamatan::on(TenantResolver::CONNECTION_A)->find($user->lokasi)
                        ?: Kecamatan::on(TenantResolver::CONNECTION_B)->find($user->lokasi);
                    $tgl_inv = $invoice->tgl_invoice ?: ($kec?->tgl_registrasi ?: $kec?->tgl_pakai);
                    $batas_toleransi = date('Y-m-d', strtotime('+1 month', strtotime($tgl_inv)));
                    if (! empty($invoice->tgl_lunas) && $invoice->tgl_lunas > $batas_toleransi) {
                        $batas_toleransi = $invoice->tgl_lunas;
                    }

                    if (date('Y-m-d') > $batas_toleransi) {
                        // Jangan blokir logout atau halaman invoice/ts agar tetap bisa diakses
                        if (! $request->is('logout') && ! $request->is('pengaturan/invoice*') && ! $request->is('pelaporan/invoice/*') && ! $request->is('pelaporan/ts')) {
                            Auth::guard('web')->logout();
                            $request->session()->invalidate();
                            $request->session()->regenerateToken();

                            return redirect('/')->with('error', 'Invoice belum terbayar');
                        }
                    }
                }
            }

            return $next($request);
        }

        // User login via 'kab' guard - pakai kd_kab dari session untuk suffix
        if (auth()->guard('kab')->check()) {
            $kabId = session('kd_kab');
            if ($kabId) {
                $kabRow = DB::connection(TenantResolver::CONNECTION_A)
                    ->table('kabupaten')
                    ->where('kd_kab', $kabId)
                    ->first();

                if (! $kabRow) {
                    $kabRow = DB::connection(TenantResolver::CONNECTION_B)
                        ->table('kabupaten')
                        ->where('kd_kab', $kabId)
                        ->first();
                }

                if ($kabRow) {
                    TenantResolver::applyResolvedConnection([
                        'connection' => $kabRow->getConnectionName(),
                    ]);
                    config(['tenant.suffix' => "_" . $kabRow->id]);
                    TenantResolver::markAsKabupaten();
                }
            }

            return $next($request);
        }

        if ($resolved && ($resolved['type'] === 'kecamatan' || $resolved['type'] === 'kabupaten')) {
            $tenantId = $resolved['tenant']->id;
            config(['tenant.suffix' => "_{$tenantId}"]);
        } else {
            config(['tenant.suffix' => '_1']);
        }

        return $next($request);
    }
}
