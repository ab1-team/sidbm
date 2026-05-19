@extends('layouts.base')

@section('css')
    <style>
        .nav-wrapper .nav-link {
            transition: all 0.2s ease !important;
        }

        .nav-wrapper .nav-link:hover:not(.active) {
            background-color: #f0f2f5 !important;
            box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-1px);
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <div class="nav-wrapper position-relative end-0">
        <ul class="nav nav-pills nav-fill p-1" role="tablist">
            @if (in_array('tahapan_perguliran.proposal', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $status == 'p' ? 'active' : '' }}" data-bs-toggle="tab"
                        href="#Proposal" role="tab" aria-controls="Proposal" aria-selected="true">
                        <span class="material-icons align-middle mb-1">
                            note_add
                        </span>
                        Proposal (P)
                    </a>
                </li>
            @endif
            @if (in_array('tahapan_perguliran.verifikasi', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $status == 'v' ? 'active' : '' }}" data-bs-toggle="tab"
                        href="#Verified" role="tab" aria-controls="Verified" aria-selected="false">
                        <span class="material-icons align-middle mb-1">
                            event_available
                        </span>
                        Verified (V)
                    </a>
                </li>
            @endif
            @if (in_array('tahapan_perguliran.waiting', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $status == 'w' ? 'active' : '' }}" data-bs-toggle="tab"
                        href="#Waiting" role="tab" aria-controls="Waiting" aria-selected="false">
                        <span class="material-icons align-middle mb-1">
                            history
                        </span>
                        Waiting (W)
                    </a>
                </li>
            @endif
            @if (in_array('tahapan_perguliran.aktif', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $status == 'a' ? 'active' : '' }}" data-bs-toggle="tab"
                        href="#Aktif" role="tab" aria-controls="Aktif" aria-selected="false">
                        <span class="material-icons align-middle mb-1">
                            import_export
                        </span>
                        Aktif (A)
                    </a>
                </li>
            @endif
            @if (in_array('tahapan_perguliran.lunas', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $status == 'l' ? 'active' : '' }}" data-bs-toggle="tab"
                        href="#Lunas" role="tab" aria-controls="Lunas" aria-selected="false">
                        <span class="material-icons align-middle mb-1">
                            cloud_done
                        </span>
                        Lunas (L)
                    </a>
                </li>
            @endif
        </ul>

        <div class="tab-content mt-2">
            @if (in_array('tahapan_perguliran.proposal', Session::get('tombol')))
                <div class="tab-pane fade {{ $status == 'p' ? 'show active' : '' }}" id="Proposal" role="tabpanel"
                    aria-labelledby="Proposal">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-flush table-hover table-click" width="100%" id="TbProposal">
                                    <thead>
                                        <tr>
                                            <th>Kelompok</th>
                                            <th>Alamat</th>
                                            <th>Tgl Pengajuan</th>
                                            <th>Pengajuan</th>
                                            <th>Jasa/Jangka</th>
                                            <th>
                                                <i class="material-icons opacity-10">people</i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if (in_array('tahapan_perguliran.verifikasi', Session::get('tombol')))
                <div class="tab-pane fade {{ $status == 'v' ? 'show active' : '' }}" id="Verified" role="tabpanel"
                    aria-labelledby="Verified">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-flush table-hover table-click" width="100%" id="TbVerified">
                                    <thead>
                                        <tr>
                                            <th>Kelompok</th>
                                            <th>Alamat</th>
                                            <th>Tgl Verified</th>
                                            <th>Verifikasi</th>
                                            <th>Jasa/Jangka</th>
                                            <th>
                                                <i class="material-icons opacity-10">people</i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if (in_array('tahapan_perguliran.waiting', Session::get('tombol')))
                <div class="tab-pane fade {{ $status == 'w' ? 'show active' : '' }}" id="Waiting" role="tabpanel"
                    aria-labelledby="Waiting">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-flush table-hover table-click" width="100%" id="TbWaiting">
                                    <thead>
                                        <tr>
                                            <th>Kelompok</th>
                                            <th>Alamat</th>
                                            <th>Tgl Waiting</th>
                                            <th>Alokasi</th>
                                            <th>Jasa/Jangka</th>
                                            <th>
                                                <i class="material-icons opacity-10">people</i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if (in_array('tahapan_perguliran.aktif', Session::get('tombol')))
                <div class="tab-pane fade {{ $status == 'a' ? 'show active' : '' }}" id="Aktif" role="tabpanel"
                    aria-labelledby="Aktif">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-flush table-hover table-click" width="100%" id="TbAktif">
                                    <thead>
                                        <tr>
                                            <th>Kelompok</th>
                                            <th>Alamat</th>
                                            <th>Tgl Cair</th>
                                            <th>Alokasi</th>
                                            <th>Jasa/Jangka</th>
                                            <th>
                                                <i class="material-icons opacity-10">people</i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if (in_array('tahapan_perguliran.lunas', Session::get('tombol')))
                <div class="tab-pane fade {{ $status == 'l' ? 'show active' : '' }}" id="Lunas" role="tabpanel"
                    aria-labelledby="Lunas">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-flush table-hover table-click" width="100%" id="TbLunas">
                                    <thead>
                                        <tr>
                                            <th>Kelompok</th>
                                            <th>Alamat</th>
                                            <th>Tgl Cair</th>
                                            <th>Verifikasi</th>
                                            <th>Jasa/Jangka</th>
                                            <th>
                                                <i class="material-icons opacity-10">people</i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        var tbProposal = CreateTable('#TbProposal', '/perguliran/proposal', [{
            data: 'nama_kelompok',
            name: 'nama_kelompok'
        }, {
            data: 'kelompok.alamat_kelompok',
            name: 'kelompok.alamat_kelompok'
        }, {
            data: 'tgl_proposal',
            name: 'tgl_proposal',
            render: function(data, type, row) {
                return moment(new Date(data).toString()).format('DD/MM/YYYY');
            }
        }, {
            data: 'proposal',
            name: 'proposal'
        }, {
            data: 'jasa',
            name: 'jasa',
            orderable: false,
            searchable: false
        }, {
            data: 'pinjaman_anggota_count',
            name: 'pinjaman_anggota_count'
        }])

        var tbVerified = CreateTable('#TbVerified', '/perguliran/verified', [{
            data: 'nama_kelompok',
            name: 'nama_kelompok'
        }, {
            data: 'kelompok.alamat_kelompok',
            name: 'kelompok.alamat_kelompok'
        }, {
            data: 'tgl_verifikasi',
            name: 'tgl_verifikasi',
            render: function(data, type, row) {
                return moment(new Date(data).toString()).format('DD/MM/YYYY');
            }
        }, {
            data: 'verifikasi',
            name: 'verifikasi'
        }, {
            data: 'jasa',
            name: 'jasa',
            orderable: false,
            searchable: false
        }, {
            data: 'pinjaman_anggota_count',
            name: 'pinjaman_anggota_count'
        }])

        var tbWaiting = CreateTable('#TbWaiting', '/perguliran/waiting', [{
            data: 'nama_kelompok',
            name: 'nama_kelompok'
        }, {
            data: 'kelompok.alamat_kelompok',
            name: 'kelompok.alamat_kelompok'
        }, {
            data: 'tgl_tunggu',
            name: 'tgl_tunggu',
            render: function(data, type, row) {
                return moment(new Date(data).toString()).format('DD/MM/YYYY');
            }
        }, {
            data: 'alokasi',
            name: 'alokasi'
        }, {
            data: 'jasa',
            name: 'jasa',
            orderable: false,
            searchable: false
        }, {
            data: 'pinjaman_anggota_count',
            name: 'pinjaman_anggota_count'
        }])

        var tbAktif = CreateTable('#TbAktif', '/perguliran/aktif', [{
            data: 'nama_kelompok',
            name: 'nama_kelompok'
        }, {
            data: 'kelompok.alamat_kelompok',
            name: 'kelompok.alamat_kelompok'
        }, {
            data: 'tgl_cair',
            name: 'tgl_cair',
            render: function(data, type, row) {
                return moment(new Date(data).toString()).format('DD/MM/YYYY');
            }
        }, {
            data: 'alokasi',
            name: 'alokasi'
        }, {
            data: 'jasa',
            name: 'jasa',
            orderable: false,
            searchable: false
        }, {
            data: 'pinjaman_anggota_count',
            name: 'pinjaman_anggota_count'
        }])

        var tbLunas = CreateTable('#TbLunas', '/perguliran/lunas', [{
            data: 'nama_kelompok',
            name: 'nama_kelompok'
        }, {
            data: 'kelompok.alamat_kelompok',
            name: 'kelompok.alamat_kelompok'
        }, {
            data: 'tgl_cair',
            name: 'tgl_cair',
            render: function(data, type, row) {
                return moment(new Date(data).toString()).format('DD/MM/YYYY');
            }
        }, {
            data: 'alokasi',
            name: 'alokasi'
        }, {
            data: 'jasa',
            name: 'jasa',
            orderable: false,
            searchable: false
        }, {
            data: 'pinjaman_anggota_count',
            name: 'pinjaman_anggota_count'
        }])

        function CreateTable(tabel, url, column) {
            var table = $(tabel).DataTable({
                language: {
                    paginate: {
                        previous: "&laquo;",
                        next: "&raquo;"
                    }
                },
                processing: true,
                serverSide: true,
                ajax: url,
                columns: column,
                order: [
                    [2, 'desc']
                ]
            })

            return table
        }

        $('#TbProposal').on('click', 'tbody tr', function(e) {
            var data = tbProposal.row(this).data();

            window.location.href = '/detail/' + data.id
        })

        $('#TbVerified').on('click', 'tbody tr', function(e) {
            var data = tbVerified.row(this).data();

            window.location.href = '/detail/' + data.id
        })

        $('#TbWaiting').on('click', 'tbody tr', function(e) {
            var data = tbWaiting.row(this).data();

            window.location.href = '/detail/' + data.id
        })

        $('#TbAktif').on('click', 'tbody tr', function(e) {
            var data = tbAktif.row(this).data();

            window.location.href = '/detail/' + data.id
        })

        $('#TbLunas').on('click', 'tbody tr', function(e) {
            var data = tbLunas.row(this).data();

            window.location.href = '/lunas/' + data.id
        })
    </script>
@endsection
