@php
    $is_dir = false;
    if (auth()->user()->jabatan == '1' && auth()->user()->level == '1') {
        $is_dir = true;
    }
@endphp

@extends('layouts.base')

@section('content')
    @php
        $saldo_pokok = $real->saldo_pokok;
        $saldo_jasa = $real->saldo_jasa;

        $keterangan1 = 'Belum Lunas';
        $keterangan2 = 'Belum Lunas';

        if ($saldo_pokok <= 0) {
            $saldo_pokok = 0;
            $keterangan1 = 'Lunas';
        }
        if ($saldo_jasa <= 0) {
            $saldo_jasa = 0;
            $keterangan2 = 'Lunas';
    } @endphp <div class="card mb-3">
        <div class="card-body p-3">
            <h5 class="mb-1">
                Kelompok {{ $perguliran->kelompok->nama_kelompok }} Loan ID. {{ $perguliran->id }}
                ({{ $perguliran->jpp->nama_jpp }})
            </h5>
            <p class="mb-0">
                <span
                    class="badge badge-{{ $perguliran->sts->warna_status }}">{{ $perguliran->kelompok->kd_kelompok }}</span>
                <span
                    class="badge badge-{{ $perguliran->sts->warna_status }}">{{ $perguliran->kelompok->alamat_kelompok }}</span>
                <span class="badge badge-{{ $perguliran->sts->warna_status }}">
                    {{ $perguliran->kelompok->d->sebutan_desa->sebutan_desa }}
                    {{ $perguliran->kelompok->d->nama_desa }}
                </span>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-sm">
            Dengan mempertimbangkan Standar Operasional Prosedur (SOP) yang berlaku, dengan ini Saya selaku
            {{ $kec->sebutan_level_1 }},
            menyatakan dengan sebenar-benarnya bahwa :
            <table class="table p-0 mb-3">
                <tr>
                    <td>Nama Kelompok</td>
                    <td>: {{ $perguliran->kelompok->nama_kelompok }}</td>
                    <td>Alokasi</td>
                    <td>{{ number_format($perguliran->alokasi) }}</td>
                </tr>
                <tr>
                    <td>Desa</td>
                    <td>: {{ $perguliran->kelompok->d->nama_desa }}</td>
                    <td>Jasa</td>
                    <td>{{ $perguliran->pros_jasa }}%</td>
                </tr>
                <tr>
                    <td>Jenis Pinjaman</td>
                    <td>: {{ $perguliran->jpp->nama_jpp }}</td>
                    <td>Sistem</td>
                    <td>{{ $perguliran->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($perguliran, false) }} / {{ $perguliran->sis_pokok->nama_sistem }}</td>
                </tr>
            </table>

            REKAPITULASI
            <table class="table f-12">
                <thead class="bg-light">
                    <tr>
                        <th>&nbsp;</th>
                        <th>Target</th>
                        <th>Realisasi</th>
                        <th>Saldo</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Pokok</td>
                        <td>{{ number_format($real->sum_pokok + $real->saldo_pokok) }}</td>
                        <td>{{ number_format($real->sum_pokok) }}</td>
                        <td>{{ number_format($saldo_pokok) }}</td>
                        <td>{{ $keterangan1 }}</td>
                    </tr>
                    <tr>
                        <td>Jasa</td>
                        <td>{{ number_format($real->sum_jasa + $real->saldo_jasa) }}</td>
                        <td>{{ number_format($real->sum_jasa) }}</td>
                        <td>{{ number_format($saldo_jasa) }}</td>
                        <td>{{ $keterangan2 }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="row mt-2">
                <div class="col-sm-4">
                    <img src="/assets/img/lunas.png">
                </div>
                <div class="col-sm-8">
                    <div class="checkbox">
                        <input id="checkboxLunaskan" type="checkbox" class="d-none">
                        <label for="checkboxLunaskan">Pinjaman tersebut diatas telah kami nyatakan
                            LUNAS dan Surat Perjanjian Kredit (SPK) nomor {{ $perguliran->spk_no }} tanggal
                            {{ Tanggal::tglLatin($perguliran->tgl_cair) }}
                            dinyatakan selesai
                            beserta
                            seluruh hak dan kewajibannya.</label>
                    </div>

                    <div class="d-flex justify-content-end" style="gap: .5em;">
                        @if (in_array('tahapan_perguliran.lunas.cetak_keterangan_pelunasan', Session::get('tombol')))
                            <button class="btn btn-warning btn-sm"
                                onclick="window.open('/cetak_keterangan_lunas/{{ $perguliran->id }}')" type="button">
                                <i class="fa fa-print"></i> Cetak Keterangan Pelunasan
                            </button>
                        @endif

                        @if ($is_dir || in_array('tahapan_perguliran.lunas.validasi_lunas', Session::get('tombol')))
                            <button class="btn btn-danger btn-sm" type="button" id="TombolLunaskan" disabled>
                                <i class="fa fa-gavel"></i> Validasi Lunas
                            </button>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <form action="/perguliran/{{ $perguliran->id }}" method="post" id="FormPelunasan">
        @csrf
        @method('PUT')

        <input type="hidden" name="status" id="status" value="L">
    </form>

    <div class="card mt-3">
        <div class="card-body p-2">
            <a href="/perguliran?status=L" class="btn btn-info float-end btn-sm mb-0">Kembali</a>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).on('click', '.checkbox', function(e) {
            var checkbox = $('#checkboxLunaskan')

            if (checkbox[0].checked == true) {
                $('#TombolLunaskan').removeAttr('disabled')
            } else {
                $('#TombolLunaskan').attr('disabled', true)
            }
        })

        $(document).on('click', '#TombolLunaskan', function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Peringatan',
                text: 'Anda akan melakukan validasi pelunasan untuk kelompok {{ $perguliran->kelompok->nama_kelompok }} dengan Loan ID. {{ $perguliran->id }}. Setelah klik tombol Lunaskan data tidak dapat diubah kembali',
                showCancelButton: true,
                confirmButtonText: 'Lunaskan',
                cancelButtonText: 'Batal',
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#FormPelunasan')

                    $.ajax({
                        type: 'post',
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                window.location.href = '/perguliran'
                            })
                        }
                    })
                }
            })
        })
    </script>
@endsection
