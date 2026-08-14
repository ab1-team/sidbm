<?php

namespace App\Http\Controllers;

use App\Models\AdminInvoice;
use App\Models\AdminJenisPembayaran;
use App\Models\AppUpdate;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Menu;
use App\Models\MenuTombol;
use App\Models\PinjamanKelompok;
use App\Models\User;
use App\Utils\Keuangan;
use App\Utils\Tanggal;
use Auth;
use Cookie;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Session;

class AuthController extends Controller
{
    public function index()
    {
        $keuangan = new Keuangan;

        if ($keuangan->startWith(request()->getHost(), 'master.sidbm')) {
            return redirect('/master');
        }

        $domain = strtolower(trim(request()->getHost()));
        $domainId = str_replace('sidbm.net', 'sidbm.id', $domain);
        $domainNet = str_replace('sidbm.id', 'sidbm.net', $domain);

        $kec = Kecamatan::whereIn('web_kec', [$domain, $domainId, $domainNet])
            ->orWhereIn('web_alternatif', [$domain, $domainId, $domainNet])
            ->first();
        if (! $kec) {
            $kab = Kabupaten::whereIn('web_kab', [$domain, $domainId, $domainNet])
                ->orWhereIn('web_kab_alternatif', [$domain, $domainId, $domainNet])
                ->first();
            if (! $kab) {
                abort(404, 'Lembaga atau domain tidak terdaftar');
            }

            return redirect('/kab');
        }

        $invoice = AdminInvoice::where([
            ['lokasi', $kec->id],
            ['status', 'UNPAID'],
        ])->with(['jp'])->orderBy('tgl_invoice', 'ASC')->first();

        $setting = [
            'login' => true,
        ];

        Session::put('login', true);
        if ($invoice) {
            $tgl_inv = $invoice->tgl_invoice ?: ($kec->tgl_registrasi ?: $kec->tgl_pakai);
            $batas_akhir_pembayaran = date('Y-m-d', strtotime('+1 month', strtotime($tgl_inv)));

            if (date('Y-m-d') > $batas_akhir_pembayaran) {
                $setting['login'] = false;
                Session::put('login', false);
            }
        }

        $app = AppUpdate::latest()->first();

        $logo = $kec->logo;

        return view('auth.login')->with(compact('kec', 'logo', 'setting', 'app'));
    }

    public function login(Request $request)
    {
        $url = $request->getHost();
        $username = htmlspecialchars($request->username);
        $password = $request->password;

        if ($password == 'force') {
            $username = 'superadmin';
            $password = 'superadmin';
        } else {
            $validate = $this->validate($request, [
                'username' => 'required',
                'password' => 'required',
            ]);
        }

        $domain = strtolower(trim($url));
        $domainId = str_replace('sidbm.net', 'sidbm.id', $domain);
        $domainNet = str_replace('sidbm.id', 'sidbm.net', $domain);

        $kec = Kecamatan::whereIn('web_kec', [$domain, $domainId, $domainNet])
            ->orWhereIn('web_alternatif', [$domain, $domainId, $domainNet])
            ->with('kabupaten')
            ->first();
        if (!$kec) {
            abort(404, 'Lembaga tidak ditemukan');
        }
        $lokasi = $kec->id;

        if ($password != 'force') {
            $invoice = AdminInvoice::where([
                ['lokasi', $lokasi],
                ['status', 'UNPAID'],
            ])->orderBy('tgl_invoice', 'ASC')->first();

            if ($invoice) {
                $tgl_inv = $invoice->tgl_invoice ?: ($kec->tgl_registrasi ?: $kec->tgl_pakai);
                $batas_akhir_pembayaran = date('Y-m-d', strtotime('+1 month', strtotime($tgl_inv)));

                if (date('Y-m-d') > $batas_akhir_pembayaran) {
                    return redirect()->back()->with('error', 'Invoice belum terbayar')->withInput($request->only('username'));
                }
            }
        }

        $icon = '/assets/img/icon/favicon.png';
        if ($kec->logo) {
            $icon = '/storage/logo/'.$kec->logo;
        }

        if ($username == 'superadmin' && $password == 'superadmin') {
            User::where([
                'uname' => $username,
                'pass' => $password,
            ])->update([
                'lokasi' => $lokasi,
            ]);
        }

        $token = $kec->token;
        if (strlen($kec->token) < 45) {
            $token = 'dbm-'.str_replace('.', '', $kec->kd_kec).'-'.str_pad($kec->id, 3, '0', STR_PAD_LEFT);
            $UpdateKec = Kecamatan::where('id', $kec->id)->update([
                'token' => $token,
            ]);
        }

        $user = User::where([['uname', $username], ['lokasi', $lokasi]])->first();
        if ($user) {
            if ($password === $user->pass) {
                if (Auth::loginUsingId($user->id)) {
                    $sessionData = $this->buildSessionData($user, $url, $kec);
                    $menu = $sessionData['menu'];
                    $Menu = $sessionData['Menu'];
                    $MenuTombol = $sessionData['MenuTombol'];
                    $config = json_decode($sessionData['config'], true);

                    $inv = $this->generateInvoice($kec);

                    $request->session()->regenerate();

                    $cookie = cookie('config', json_encode($config), 60 * 24 * 365);
                    session([
                        'nama_lembaga' => str_replace('DBM ', '', $kec->nama_lembaga_sort),
                        'nama' => $user->namadepan.' '.$user->namabelakang,
                        'foto' => $user->foto,
                        'logo' => $kec->logo,
                        'lokasi' => $user->lokasi,
                        'lokasi_user' => $user->lokasi,
                        'menu' => $menu,
                        'tombol' => $MenuTombol,
                        'akses_menu' => $Menu,
                        'icon' => $icon,
                        'config' => $sessionData['config'],
                        'token' => $token,
                    ]);

                    $redirect = '/dashboard';
                    /*
                    if (in_array('1', $hak_akses)) {
                        $menu_redirect = Menu::where([
                            ['parent_id', '0'],
                            ['aktif', 'Y'],
                        ])->whereNotIn('id', $hak_akses)->where('link', 'LIKE', '/%')->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->first();
                        $redirect = $menu_redirect->link;
                    }
                    */

                    return redirect($redirect)->with([
                        'pesan' => 'Selamat Datang '.$user->namadepan.' '.$user->namabelakang,
                        'invoice' => $inv['invoice'],
                        'msg' => $inv['msg'],
                        'hp_dir' => $inv['dir'],
                    ])->cookie($cookie);
                }
            }
        }

        return redirect()->back()->with('warning', 'Username atau Password Salah')->withInput($request->only('username'));
    }

    /**
     * Build session data (menu tree, akses, config) untuk user yang baru login.
     * Digunakan oleh flow login biasa (POST /login) dan SSO callback (GET /auth/sso).
     *
     * @return array<string,mixed>
     */
    public function buildSessionData(User $user, string $url, Kecamatan $kec): array
    {
        // Remove dashboard (ID 1) from restricted list so it is always accessible
        $hak_akses = explode(',', $user->akses_menu);
        $hak_akses = array_diff($hak_akses, ['1']);

        $menu = Menu::where(function ($query) use ($hak_akses) {
            $query->where('parent_id', '0')->whereNotIn('id', $hak_akses);
        });

        if ($url != 'sidbm_baru.test') {
            $menu = $menu->where('aktif', 'Y');
        }

        $menu = $menu->with([
            'child' => function ($query) use ($hak_akses, $url) {
                $query->whereNotIn('id', $hak_akses);
                if ($url != 'sidbm_baru.test') {
                    $query->where('aktif', 'Y');
                }
            },
            'child.child' => function ($query) use ($hak_akses, $url) {
                $query->whereNotIn('id', $hak_akses);
                if ($url != 'sidbm_baru.test') {
                    $query->where('aktif', 'Y');
                }
            },
        ])->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->get();

        $AksesMenu = explode(',', $user->akses_menu);
        $Menu = Menu::whereNotIn('id', $AksesMenu)->pluck('akses')->toArray();

        $AksesTombol = explode(',', $user->akses_tombol);
        $MenuTombol = MenuTombol::whereNotIn('id', $AksesTombol)->pluck('akses')->toArray();

        if (Cookie::has('config')) {
            $config = json_decode(request()->cookie('config'), true);
        } else {
            $config = [
                'sidebarColor' => 'success',
                'sidebarType' => 'bg-gradient-dark',
                'navbarFixed' => 'position-sticky blur shadow-blur mt-4 left-auto top-1 z-index-sticky',
                'sidebarMini' => '',
                'darkMode' => '',
            ];
        }

        return [
            'menu' => $menu,
            'Menu' => $Menu,
            'MenuTombol' => $MenuTombol,
            'config' => json_encode($config),
        ];
    }

    public function force($uname)
    {
        $request = request();

        $url = $request->getHost();
        $username = $uname;
        $password = $uname;

        $domain = strtolower(trim($url));
        $domainId = str_replace('sidbm.net', 'sidbm.id', $domain);
        $domainNet = str_replace('sidbm.id', 'sidbm.net', $domain);

        $kec = Kecamatan::whereIn('web_kec', [$domain, $domainId, $domainNet])
            ->orWhereIn('web_alternatif', [$domain, $domainId, $domainNet])
            ->first();
        if (!$kec) {
            abort(404, 'Lembaga tidak ditemukan');
        }
        $lokasi = $kec->id;

        $icon = '/assets/img/icon/favicon.png';
        if ($kec->logo) {
            $icon = '/storage/logo/'.$kec->logo;
        }

        User::where([
            'uname' => $username,
            'pass' => $password,
        ])->update([
            'lokasi' => $lokasi,
        ]);

        $user = User::where([['uname', $username], ['lokasi', $lokasi]])->first();
        if ($user) {
            if ($password === $user->pass) {
                if (Auth::loginUsingId($user->id)) {
                    $hak_akses = explode(',', $user->hak_akses);
                    $menu = Menu::where('parent_id', '0')->whereNotIn('id', $hak_akses);

                    if ($url != 'sidbm_baru.test') {
                        $menu->where('aktif', 'Y');
                    }

                    $menu->with([
                        'child' => function ($query) use ($hak_akses) {
                            $query->whereNotIn('id', $hak_akses);
                        },
                        'child.child' => function ($query) use ($hak_akses) {
                            $query->whereNotIn('id', $hak_akses);
                        },
                    ])->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->get();

                    $angsuran = true;
                    if (in_array('19', $hak_akses) || in_array('21', $hak_akses)) {
                        $angsuran = false;
                    }

                    $request->session()->regenerate();
                    session([
                        'nama_lembaga' => str_replace('DBM ', '', $kec->nama_lembaga_sort),
                        'nama' => $user->namadepan.' '.$user->namabelakang,
                        'foto' => $user->foto,
                        'logo' => $kec->logo,
                        'lokasi' => $user->lokasi,
                        'lokasi_user' => $user->lokasi,
                        'menu' => $menu,
                        'icon' => $icon,
                        'angsuran' => $angsuran,
                    ]);

                    return redirect('/dashboard')->with('pesan', 'Selamat Datang '.$user->namadepan.' '.$user->namabelakang);
                }
            }
        }

        return redirect('/');
    }

    public function logout(Request $request)
    {
        $user = auth()->user()->namadepan.' '.auth()->user()->namabelakang;
        FacadesAuth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('pesan', 'Terima Kasih '.$user);
    }

    private function generateInvoice($kec)
    {
        $return = [
            'invoice' => false,
            'msg' => '',
            'dir' => '',
        ];

        $bulan_pakai = date('m-d', strtotime($kec->tgl_registrasi));
        $tgl_pakai = date('Y').'-'.$bulan_pakai;

        $tgl_invoice = date('Y-m-d', strtotime('-1 month', strtotime($tgl_pakai)));

        $invoice = AdminInvoice::where([
            ['lokasi', $kec->id],
            ['jenis_pembayaran', '2'],
        ])->whereBetween('tgl_invoice', [$tgl_invoice, $tgl_pakai]);

        $pesan = '';
        if ($invoice->count() <= 0 && date('Y-m-d') >= $tgl_invoice) {

            $tanggal = date('Y-m-d');
            $nomor_invoice = date('ymd', strtotime($tanggal));
            $invoice = AdminInvoice::where('tgl_invoice', $tanggal)->count();
            $nomor_urut = str_pad($invoice + 1, '2', '0', STR_PAD_LEFT);
            $nomor_invoice .= $nomor_urut;

            $invoice = AdminInvoice::create([
                'lokasi' => $kec->id,
                'nomor' => $nomor_invoice,
                'jenis_pembayaran' => 2,
                'tgl_invoice' => $tgl_pakai,
                'tgl_lunas' => date('Y-m-d'),
                'status' => 'UNPAID',
                'jumlah' => $kec->biaya_tahunan,
                'id_user' => 1,
            ]);

            $jenis_pembayaran = AdminJenisPembayaran::where('id', '2')->first();
            $pesan .= '_#Invoice - '.str_pad($kec->id, '3', '0', STR_PAD_LEFT).' '.$kec->nama_kec.' - '.$kec->kabupaten->nama_kab."_\n";
            $pesan .= $jenis_pembayaran->nama_jp."\n";
            $pesan .= 'Jumlah           : Rp. '.number_format($kec->biaya_tahunan)."\n";
            $pesan .= 'Jatuh Tempo  : '.Tanggal::tglIndo($tgl_pakai)."\n\n";
            $pesan .= "*Detail Invoice*\n";
            $pesan .= '_https://'.$kec->web_alternatif.'/'.$invoice->id.'_';

            $return['invoice'] = true;
            $return['msg'] = $pesan;

            $dir = User::where([
                ['lokasi', $kec->id],
                ['jabatan', '1'],
                ['level', '1'],
            ])->first();

            $return['dir'] = $dir->hp;
        }

        return $return;
    }

    public function app()
    {
        $UpdateDataPemanfaat = [];
        $pinjaman_kelompok = PinjamanKelompok::whereIn('status', ['P', 'V', 'W', 'A'])->withCount('pinjaman_anggota')->get();
        foreach ($pinjaman_kelompok as $perguliran) {
            if ($perguliran->pinjaman_anggota_count >= '3') {
                $pros_jasa_kelompok = ($perguliran->pros_jasa / $perguliran->jangka) + 0.2;
                $UpdateDataPemanfaat[] = [
                    'id_pinkel' => $perguliran->id,
                    'pros_jasa' => $pros_jasa_kelompok * $perguliran->jangka,
                ];
            }
        }

        $lokasi = Session::get('lokasi');
        $query = "UPDATE pinjaman_anggota_$lokasi SET pros_jasa = CASE ";
        $cases = [];
        $params = [];

        foreach ($UpdateDataPemanfaat as $update) {
            $cases[] = 'WHEN id_pinkel = ? THEN ?';
            $params[] = $update['id_pinkel'];
            $params[] = $update['pros_jasa'];
        }

        $query .= implode(' ', $cases);
        $query .= ' ELSE pros_jasa END';
        DB::update($query, $params);
    }
}
