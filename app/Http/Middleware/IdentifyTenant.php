<?php

namespace App\Http\Middleware;

use App\Models\AdminInvoice;
use App\Models\Kecamatan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $domain = strtolower(trim($request->getHost()));
        $domainId = str_replace('sidbm.net', 'sidbm.id', $domain);
        $domainNet = str_replace('sidbm.id', 'sidbm.net', $domain);

        // 1. Cek di mysql_b (holding) — kecamatan dulu
        $tenantFromB = DB::connection('mysql_b')
            ->table('kecamatan')
            ->whereIn('web_kec', [$domain, $domainId, $domainNet])
            ->orWhereIn('web_alternatif', [$domain, $domainId, $domainNet])
            ->first();

        // 2. Kalau bukan kecamatan, cek kabupaten di mysql_b
        $kabFromB = null;
        if (! $tenantFromB) {
            $kabFromB = DB::connection('mysql_b')
                ->table('kabupaten')
                ->whereIn('web_kab', [$domain, $domainId, $domainNet])
                ->orWhereIn('web_kab_alternatif', [$domain, $domainId, $domainNet])
                ->first();
        }

        // 3. Domain milik tenant (kecamatan ATAU kabupaten) — pakai holding DB
        if ($tenantFromB || $kabFromB) {
            config(['database.default' => 'mysql_b']);
        }

        // 4. Set flag untuk downstream (controller / view) bahwa ini request kabupaten
        config(['tenant.is_kab' => (bool) $kabFromB]);

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

                if ($invoice && date('Y-m-d') >= $invoice->tgl_invoice) {
                    // Jangan blokir logout atau halaman invoice/ts agar tetap bisa diakses
                    if (! $request->is('logout') && ! $request->is('pengaturan/invoice*') && ! $request->is('pelaporan/invoice/*') && ! $request->is('pelaporan/ts')) {
                        Auth::guard('web')->logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return redirect('/')->with('error', 'Invoice belum terbayar');
                    }
                }
            }

            return $next($request);
        }

        // User login via 'kab' guard — pakai kd_kab dari session untuk suffix
        if (auth()->guard('kab')->check()) {
            $kabId = session('kd_kab');
            if ($kabId) {
                $kabRow = DB::connection('mysql_b')
                    ->table('kabupaten')
                    ->where('kd_kab', $kabId)
                    ->first();
                if ($kabRow) {
                    config(['tenant.suffix' => "_{$kabRow->id}"]);
                    config(['tenant.is_kab' => true]);
                }
            }

            return $next($request);
        }

        if ($tenantFromB) {
            $tenant = Kecamatan::on('mysql_b')->find($tenantFromB->id);
        } elseif ($kabFromB) {
            // Domain kabupaten — suffix pakai id_kab agar konsistensi dengan tabel pinjaman_kab_*
            $tenant = (object) ['id' => $kabFromB->id];
        } else {
            // Fallback: cek default DB (untuk domain lokal/development)
            $tenant = Kecamatan::whereIn('web_kec', [$domain, $domainId, $domainNet])
                ->orWhereIn('web_alternatif', [$domain, $domainId, $domainNet])
                ->first();
        }

        $suffix = $tenant ? "_{$tenant->id}" : '_1';
        config(['tenant.suffix' => $suffix]);

        return $next($request);
    }
}
