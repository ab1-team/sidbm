@php
    $thn_awal = explode('-', $kec->tgl_pakai)[0];
@endphp

@extends('layouts.base')

@section('content')
    <div class="card mb-3">
        <div class="card-body pb-0">

            <form action="/pelaporan/preview" method="post" id="FormPelaporan" target="_blank">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="tahun">Tahunan</label>
                            <select class="form-control" name="tahun" id="tahun">
                                <option value="">---</option>
                                @for ($i = date('Y'); $i >= $thn_awal; $i--)
                                    <option {{ $i == date('Y') ? 'selected' : '' }} value="{{ $i }}">
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <small class="text-danger" id="msg_tahun"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="bulan">Bulanan</label>
                            <select class="form-control" name="bulan" id="bulan">
                                <option value="">---</option>
                                <option {{ date('m') == '01' ? 'selected' : '' }} value="01">01. JANUARI</option>
                                <option {{ date('m') == '02' ? 'selected' : '' }} value="02">02. FEBRUARI</option>
                                <option {{ date('m') == '03' ? 'selected' : '' }} value="03">03. MARET</option>
                                <option {{ date('m') == '04' ? 'selected' : '' }} value="04">04. APRIL</option>
                                <option {{ date('m') == '05' ? 'selected' : '' }} value="05">05. MEI</option>
                                <option {{ date('m') == '06' ? 'selected' : '' }} value="06">06. JUNI</option>
                                <option {{ date('m') == '07' ? 'selected' : '' }} value="07">07. JULI</option>
                                <option {{ date('m') == '08' ? 'selected' : '' }} value="08">08. AGUSTUS</option>
                                <option {{ date('m') == '09' ? 'selected' : '' }} value="09">09. SEPTEMBER</option>
                                <option {{ date('m') == '10' ? 'selected' : '' }} value="10">10. OKTOBER</option>
                                <option {{ date('m') == '11' ? 'selected' : '' }} value="11">11. NOVEMBER</option>
                                <option {{ date('m') == '12' ? 'selected' : '' }} value="12">12. DESEMBER</option>
                            </select>
                            <small class="text-danger" id="msg_bulan"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="hari">Harian</label>
                            <select class="form-control" name="hari" id="hari">
                                <option value="">---</option>
                                @for ($j = 1; $j <= 31; $j++)
                                    @if ($j < 10)
                                        <option value="0{{ $j }}">0{{ $j }}</option>
                                    @else
                                        <option value="{{ $j }}">{{ $j }}</option>
                                    @endif
                                @endfor
                            </select>
                            <small class="text-danger" id="msg_hari"></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div id="namaLaporan" class="col-md-6">
                        <div class="my-2">
                            <label class="form-label" for="laporan">Nama Laporan</label>
                            <select class="form-control" name="laporan" id="laporan">
                                <option value="">---</option>
                                @foreach ($laporan as $lap)
                                    <option value="{{ $lap->id }}|{{ $lap->file }}">
                                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}.
                                        {{ $lap->nama_laporan }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger" id="msg_laporan"></small>
                        </div>
                    </div>
                    <div class="col-md-6" id="subLaporan">
                        <div class="my-2">
                            <label class="form-label" for="sub_laporan">Nama Sub Laporan</label>
                            <select class="form-control" name="sub_laporan" id="sub_laporan">
                                <option value="">---</option>
                            </select>
                            <small class="text-danger" id="msg_sub_laporan"></small>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="type" id="type" value="pdf">
            </form>

            <div class="d-flex justify-content-end">
                @if (in_array('laporan.simpan_saldo', Session::get('tombol')))
                    <button type="button" id="SimpanSaldo" class="btn btn-sm btn-danger me-2">Simpan Saldo</button>
                @endif
                @if (in_array('laporan.download_excel', Session::get('tombol')))
                    <button type="button" id="Excel" class="btn btn-sm btn-success me-2" disabled
                        title="Pilih nama laporan terlebih dahulu">Excel</button>
                @endif
                @if (in_array('laporan.preview_pdf', Session::get('tombol')))
                    <button type="button" id="Preview" class="btn btn-sm btn-github" disabled
                        title="Pilih nama laporan terlebih dahulu">Preview</button>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2" id="LayoutPreview">
            <div class="p-5"></div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        new Choices($('select#tahun')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('select#bulan')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        var tanggal_laporan = new Choices($('select#hari')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('select#laporan')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        var select_sub_laporan = new Choices($('select#sub_laporan')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })

        $(document).on('change', '#tahun, #bulan', function(e) {
            e.preventDefault()

            var laporan = $('select#laporan').val().split('|')
            var id = laporan[0]
            var file = laporan[1]

            subLaporan(file)
        })

        $(document).on('change', '#laporan', function(e) {
            e.preventDefault()

            var laporan = $(this).val().split('|')
            var id = laporan[0]
            var file = laporan[1]

            if (id >= 7 && id <= 30) {
                Toastr('warning', 'Laporan hanya tersedia versi bulanan')

                tanggal_laporan.setChoiceByValue('')
            }

            toggleActionButtons()

            subLaporan(file)
        })

        function subLaporan(file) {
            var tahun = $('select#tahun').val()
            var bulan = $('select#bulan').val()
            var sub_laporan = $('select#sub_laporan').val()

            $.get('/pelaporan/sub_laporan/' + file + '?tahun=' + tahun + '&bulan=' + bulan, function(result) {
                $('#subLaporan').html(result)

                if (file == 'calk') {
                    $('#namaLaporan').removeClass('col-md-6')
                    $('#namaLaporan').addClass('col-md-12')
                    $('#subLaporan').removeClass('col-md-6')
                    $('#subLaporan').addClass('col-md-12')
                } else {
                    $('#namaLaporan').removeClass('col-md-12')
                    $('#namaLaporan').addClass('col-md-6')
                    $('#subLaporan').removeClass('col-md-12')
                    $('#subLaporan').addClass('col-md-6')
                }

                select_sub_laporan.setChoiceByValue(sub_laporan)
            })
        }

        function toggleActionButtons() {
            var laporan = $('select#laporan').val()
            var file = ''
            if (laporan && laporan.indexOf('|') !== -1) {
                file = laporan.split('|')[1]
            }

            var butuh_sub = file == 'buku_besar'
            var sub_laporan = $('select#sub_laporan').val()

            if (!laporan || laporan == '') {
                $('#Preview').prop('disabled', true).attr('title', 'Pilih nama laporan terlebih dahulu')
                $('#Excel').prop('disabled', true).attr('title', 'Pilih nama laporan terlebih dahulu')
            } else if (butuh_sub && (!sub_laporan || sub_laporan == '')) {
                $('#Preview').prop('disabled', true).attr('title', 'Pilih sub laporan terlebih dahulu')
                $('#Excel').prop('disabled', true).attr('title', 'Pilih sub laporan terlebih dahulu')
            } else {
                $('#Preview').prop('disabled', false).removeAttr('title')
                $('#Excel').prop('disabled', false).removeAttr('title')
            }
        }

        toggleActionButtons()

        $(document).on('change', '#sub_laporan', function(e) {
            toggleActionButtons()
        })

        var quill = new Quill('#editor', {
            theme: 'snow'
        });

        $(document).on('click', '#Preview', async function(e) {
            e.preventDefault()

            $(this).parent('div').parent('div').find('form').find('#type').val('pdf')
            var file = $('select#laporan').val()
            if (file == '30|calk') {
                await $('textarea#sub_laporan').val(quill.container.firstChild.innerHTML)
            }

            var form = $('#FormPelaporan')
            if (file != '') {
                form.submit()
            }
        })

        $(document).on('click', '#Excel', async function(e) {
            e.preventDefault()

            $(this).parent('div').parent('div').find('form').find('#type').val('excel')
            var file = $('select#laporan').val()
            if (file == 'calk') {
                await $('textarea#sub_laporan').val(quill.container.firstChild.innerHTML)
            }

            var form = $('#FormPelaporan')
            console.log(form.serialize())
            if (file != '') {
                form.submit()
            }
        })

        let childWindow, loading;
        $(document).on('click', '#SimpanSaldo', function(e) {
            e.preventDefault()

            var tahun = $('select#tahun').val()
            var bulan = $('select#bulan').val()
            if (bulan < 1) {
                bulan = 0
            }

            var nama_bulan = namaBulan(bulan)

            var pesan = nama_bulan + " sampai Desember "
            if (bulan == '12') {
                pesan = nama_bulan + " "
            }

            loading = Swal.fire({
                title: "Mohon Menunggu..",
                html: "Menyimpan Saldo Bulan " + pesan + tahun,
                timerProgressBar: true,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            })

            childWindow = window.open('/simpan_saldo?tahun=' + tahun + '&bulan=' + bulan, '_blank');
        })

        window.addEventListener('message', function(event) {
            if (event.data === 'closed') {
                loading.close()
                window.location.reload()
            }
        })

        function namaBulan(bulan) {
            switch (bulan) {
                case '01':
                    return 'Januari';
                    break;
                case '02':
                    return 'Februari';
                    break;
                case '03':
                    return 'Maret';
                    break;
                case '04':
                    return 'April';
                    break;
                case '05':
                    return 'Mei';
                    break;
                case '06':
                    return 'Juni';
                    break;
                case '07':
                    return 'Juli';
                    break;
                case '08':
                    return 'Agustus';
                    break;
                case '09':
                    return 'September';
                    break;
                case '10':
                    return 'Oktober';
                    break;
                case '11':
                    return 'November';
                    break;
                case '12':
                    return 'Desember';
                    break;
            }

            return 'Januari';
        }
    </script>
@endsection
