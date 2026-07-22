@extends('admin.layout.base')

@section('content')
    <div class="alert alert-info text-white fw-bold">
        #Invoice{{ $invoice->idv }} - {{ $invoice->kec->id }} {{ $invoice->kec->nama_kec }} -
        {{ $invoice->kec->kabupaten->nama_kab }} {{ $invoice->tgl_lunas }}
        Rp. {{ number_format($invoice->jumlah) }}
    </div>
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <form action="/master/{{ $invoice->idv }}/simpan" method="post" id="FormInvoice">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="tgl_terima">Diterima Pada Tanggal</label>
                                    <input autocomplete="off" type="text" name="tgl_terima" id="tgl_terima"
                                        class="form-control date" value="{{ date('d/m/Y') }}">
                                    <small class="text-danger" id="msg_tgl_terima"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="jumlah">Jumlah</label>
                                    <input autocomplete="off" type="text" name="jumlah" id="jumlah"
                                        class="form-control"
                                        value="{{ number_format($invoice->jumlah - $invoice->trx_sum_jumlah, 2) }}">
                                    <small class="text-danger" id="msg_jumlah"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="keterangan">Keterangan</label>
                                    <input autocomplete="off" type="text" name="keterangan" id="keterangan"
                                        class="form-control"
                                        value="{{ $invoice->jp->nama_jp }} Kec. {{ $invoice->kec->nama_kec }}">
                                    <small class="text-danger" id="msg_keterangan"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="my-2">
                                    <label class="form-label" for="metode_pembayaran">Metode Pembayaran</label>
                                    <select class="form-control" name="metode_pembayaran" id="metode_pembayaran">
                                        @foreach ($rekening as $rk)
                                            <option value="{{ $rk->kd_rekening }}">
                                                {{ $rk->nama_rekening }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-danger" id="msg_metode_pembayaran"></small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <a href="/pelaporan/invoice/{{ $invoice->idv }}" target="_blank"
                                class="btn btn-success btn-sm mb-0 me-3">
                                Invoice
                            </a>

                            <button type="submit" id="SimpanInvoice" class="btn btn-github btn-sm mb-0">
                                Simpan Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="input-group input-group-static">
                        <label for="pesan">Pesan</label>
                        <textarea class="form-control" rows="6" name="pesan" id="pesan"></textarea>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-sm btn-info mb-0" type="button">Kirim Pesan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var tgl = "{{ date('d/m/Y') }}"
        var jumlah = '{{ number_format($invoice->jumlah - $invoice->trx_sum_jumlah, 2) }}'
        var keterangan = '{{ $invoice->jp->nama_jp }} Kec. {{ $invoice->kec->nama_kec }}'
        var lembaga = '{{ $invoice->kec->nama_lembaga_sort }}'

        pesan()
        new Choices($('#metode_pembayaran')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })

        $(".date").flatpickr({
            dateFormat: "d/m/Y"
        })

        $("#jumlah").maskMoney();

        $(document).on('change', '#tgl_terima, #jumlah, #keterangan', function() {
            tgl = $('#tgl_terima').val()
            jumlah = $('#jumlah').val()
            keterangan = $('#keterangan').val()

            if (jumlah == '' || jumlah == undefined || jumlah == NaN) {
                jumlah = 0
            }

            pesan()
        })

        $(document).on('click', '#SimpanInvoice', function(e) {
            e.preventDefault()

            var form = $('#FormInvoice')
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        var saldo = result.saldo
                        var id = result.id
                        Swal.fire('Berhasil', result.msg, 'success').then(() => {
                            if (!result.lunas) {
                                Swal.fire({
                                    title: 'Informasi',
                                    text: "Sisa tagihan yang tersisa adalah sebesar Rp " +
                                        result.saldo,
                                    icon: 'info',
                                }).then((res) => {
                                    window.location.href = '/master/unpaid/'
                                })
                            } else {
                                window.location.href = '/master/' + id + '/paid'
                            }
                        })
                    }
                },
                error: function(result) {
                    const respons = result.responseJSON;
                    Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error')
                    $.map(respons, function(res, key) {
                        $('#' + key).parent('.input-group.input-group-static').addClass(
                            'is-invalid')
                        $('#msg_' + key).html(res)
                    })
                }
            })
        })

        function pesan() {
            var pesan =
                `*${keterangan}*\n\n*Yth. ${lembaga}*\nTerima kasih telah melakukan pembayaran *${keterangan}* sebesar *Rp. ${jumlah}* pada tanggal *${tgl}*. Silakan cetak Invoice Paid di menu Pengaturan - Invoice.\n\nSalam,\nPT. Asta Brata Teknologi`
            $('#pesan').val(pesan)
        }
    </script>
@endsection
