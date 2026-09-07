@php
    function active($curent, ...$_url)
    {
        $jumlah_url = count(request()->segments());
        $url = request()->segment($jumlah_url);

        if ($curent == $url) {
            return 'active';
        }

        if (in_array($url, $_url)) {
            return 'active';
        }

        if (in_array(request()->segment($jumlah_url - 1), $_url)) {
            return 'active';
        }

        return '';
    }

@endphp

<aside
    class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   bg-gradient-dark"
    id="sidenav-main" data-color="success">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand text-center" href="/master/dashboard">
            <span class="ms-1 font-weight-bold text-white" id="nama_lembaga_sort">
                {{ Session::get('nama_kab') }} Page
            </span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item mb-2 mt-0">
                <a href="#" class="nav-link text-white">
                    <img src="https://w7.pngwing.com/pngs/326/629/png-transparent-desktop-pc-pc-computer-calculator-icon.png"
                        class="avatar" id="profil_avatar">
                    <span class="nav-link-text ms-2 ps-1 nama_user">{{ Session::get('nama_kab') }}</span>
                </a>
            </li>
            <hr class="horizontal light mt-0">

            <li class="nav-item nav-item-link {{ active('dashboard') }}">
                <a class="nav-link text-white {{ active('dashboard') }}" href="/kab/dashboard">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">edit</i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>

            <li class="nav-item nav-item-link {{ active('tanda_tangan') }}">
                <a class="nav-link text-white {{ active('tanda_tangan') }}" href="/kab/tanda_tangan">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">dashboard</i>
                    </div>
                    <span class="nav-link-text ms-1">Tanda Tangan Laporan</span>
                </a>
            </li>

            <li class="nav-item nav-item-link {{ active('profil') }}">
                <a class="nav-link text-white {{ active('profil') }}" href="/kab/profil">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">manage_accounts</i>
                    </div>
                    <span class="nav-link-text ms-1">Profil Kabupaten</span>
                </a>
            </li>

            @php
                $path = Request::path();
                $path = explode('/', $path);
            @endphp


            <li class="nav-item mt-3">
                <h6 class="ps-4  ms-2 text-uppercase text-xs font-weight-bolder text-white">Laporan Keuangan</h6>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#MenuKecamatan"
                    class="nav-link text-white {{ active('', 'kecamatan') }}" aria-controls="MenuKecamatan"
                    role="button" aria-expanded="false">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons-round opacity-10">assessment</i>
                    </div>
                    <span class="nav-link-text ms-1">Per Kecamatan</span>
                </a>
                <div class="collapse" id="MenuKecamatan">
                    <ul class="nav nav-sm flex-column">
                        @foreach (Session::get('kecamatan') as $kec)
                            <li class="nav-item nav-item-link {{ active($kec->kode) }}">
                                <a class="nav-link text-white {{ active($kec->kode) }}"
                                    href="/kab/kecamatan/{{ $kec->kode }}"">
                                    <span class="sidenav-mini-icon">
                                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="sidenav-normal  ms-2  ps-1"> {{ ucwords(strtolower($kec->nama)) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>

            <li class="nav-item nav-item-link {{ active('laporan') }}">
                <a class="nav-link text-white {{ active('laporan') }}" href="/kab/laporan">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">insert_drive_file</i>
                    </div>
                    <span class="nav-link-text ms-1">Rekap Kabupaten</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
