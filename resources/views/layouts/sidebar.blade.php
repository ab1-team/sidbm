@php

@endphp

<aside
    class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 {{ $config['sidebarType'] }}"
    id="sidenav-main" data-color="{{ $config['sidebarColor'] }}">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand text-center" href="/dashboard">
            <span class="ms-1 font-weight-bold text-white" id="nama_lembaga_sort">
                {{ Session::get('nama_lembaga') }}
            </span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item mb-2 mt-0">
                <a href="/profil" class="nav-link text-white">
                    <img src="/user-foto" onerror="this.onerror=null;this.src='/assets/img/avatar/male.jpg'" class="avatar" id="profil_avatar">
                    <span class="nav-link-text ms-2 ps-1 nama_user">{{ Session::get('nama') }}</span>
                </a>
            </li>
            <hr class="horizontal light mt-0">

            @include('layouts.menu', ['parent_menu' => Session::get('menu')])

            <hr class="horizontal light mt-0">
            @if (Session::get('lokasi') != '1')
                <li class="nav-item nav-item-link">
                    <a class="nav-link text-white {{ Request::path() == 'pengaturan/panduan_transaksi' ? 'active' : '' }}"
                        href="/pengaturan/panduan_transaksi">
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">description</i>
                        </div>
                        <span class="nav-link-text ms-1">Panduan Transaksi</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</aside>
