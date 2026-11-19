<div class="card">
    <div class="card-header p-3 pt-2">
        <div class="icon icon-lg icon-shape bg-gradient-dark shadow text-center border-radius-xl mt-n4 me-3 float-start">
            <i class="material-icons opacity-10">note_add</i>
        </div>
        <h6 class="mb-0">
            Register Proposal {{ $kelompok->jenis_produk_pinjaman != '3' ? 'Kelompok' : 'Usaha' }}
            {{ $kelompok->nama_kelompok }}
        </h6>
        <div class="text-xs">
            {{ $kelompok->d->sebutan_desa->sebutan_desa }} {{ $kelompok->d->nama_desa }},
            {{ $kelompok->d->kd_desa }}
        </div>
    </div>
    <div class="card-body pt-0">
        <form action="/perguliran" method="post" id="FormRegisterProposal">
            @csrf

            <input type="hidden" name="id_kel" id="id_kel" value="{{ $kelompok->id }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="input-group input-group-static my-3">
                        <label for="tgl_proposal">Tgl Proposal</label>
                        <input autocomplete="off" type="text" name="tgl_proposal" id="tgl_proposal"
                            class="form-control date" value="{{ date('d/m/Y') }}">
                        <small class="text-danger" id="msg_tgl_proposal"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-static my-3">
                        <label for="pengajuan">Pengajuan Rp.</label>
                        <input autocomplete="off" type="text" name="pengajuan" id="pengajuan" class="form-control">
                        <small class="text-danger" id="msg_pengajuan"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-static my-3">
                        <label for="jangka">Jangka</label>
                        <input autocomplete="off" type="number" name="jangka" id="jangka" class="form-control"
                            value="{{ $kec->def_jangka }}">
                        <small class="text-danger" id="msg_jangka"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-static my-3">
                        <label for="pros_jasa">Prosentase Jasa (%)</label>
                        <input autocomplete="off" type="number" name="pros_jasa" id="pros_jasa" class="form-control"
                            value="{{ $kec->def_jasa }}">
                        <small class="text-danger" id="msg_pros_jasa"></small>
                    </div>
                </div>
            </div>

            @php
                $class1 = 'col-md-6';
                $class2 = 'col-md-6';
                $class3 = 'col-md-6';
                if ($kelompok->jenis_produk_pinjaman == '3') {
                    $class1 = 'col-md-2';
                    $class2 = 'col-md-5';
                    $class3 = 'col-md-5';
                }
            @endphp

            <div class="row">
                <div class="{{ $class1 }}">
                    <div class="my-2">
                        <label class="form-label" for="jenis_jasa">Jenis Jasa</label>
                        <select class="form-control" name="jenis_jasa" id="jenis_jasa">
                            @foreach ($jenis_jasa as $jj)
                                <option value="{{ $jj->id }}">
                                    {{ $jj->nama_jj }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" id="msg_jenis_jasa"></small>
                    </div>
                </div>
                <div class="{{ $class1 }} {{ $kelompok->jenis_produk_pinjaman == '3' ? 'd-none' : '' }}">
                    <div class="my-2">
                        <label class="form-label" for="jenis_produk_pinjaman">Jenis Produk Pinjaman</label>
                        <select class="form-control" name="jenis_produk_pinjaman" id="jenis_produk_pinjaman">
                            @foreach ($jenis_pp as $jpp)
                                <option {{ $jenis_pp_dipilih == $jpp->id ? 'selected' : '' }}
                                    value="{{ $jpp->id }}">
                                    {{ $jpp->nama_jpp }} ({{ $jpp->deskripsi_jpp }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" id="msg_jenis_produk_pinjaman"></small>
                    </div>
                </div>

                <div class="{{ $class2 }}">
                    <div class="my-2">
                        <label class="form-label" for="sistem_angsuran_pokok">Sistem Angs. Pokok</label>
                        <select class="form-control" name="sistem_angsuran_pokok" id="sistem_angsuran_pokok">
                            @foreach ($sistem_angsuran as $sa)
                                <option value="{{ $sa->id }}">
                                    {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" id="msg_sistem_angsuran_pokok"></small>
                    </div>
                </div>
                <div class="{{ $class3 }}">
                    <div class="my-2">
                        <label class="form-label" for="sistem_angsuran_jasa">Sistem Angs. Jasa</label>
                        <select class="form-control" name="sistem_angsuran_jasa" id="sistem_angsuran_jasa">
                            @foreach ($sistem_angsuran as $sa)
                                <option value="{{ $sa->id }}">
                                    {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger" id="msg_sistem_angsuran_jasa"></small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="my-2">
                        <label class="form-label" for="pembulatan">Pembulatan (opsional)</label>
                        <select class="form-control" name="pembulatan" id="pembulatan">
                            <option value="">Ikuti pengaturan kecamatan ({{ $kec->pembulatan }})</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                        </select>
                        <small class="form-text">Berlaku hanya untuk pinjaman ini.</small>
                        <small class="text-danger" id="msg_pembulatan"></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="my-2">
                        <label class="form-label" for="sistem_pembulatan">Sistem Pembulatan</label>
                        <select class="form-control" name="sistem_pembulatan" id="sistem_pembulatan">
                            <option value="" selected>Auto</option>
                            <option value="+">Keatas</option>
                            <option value="-">Kebawah</option>
                        </select>
                        <small class="text-danger" id="msg_sistem_pembulatan"></small>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body p-2">
                    <div class="text-center fw-bold">
                        Struktur {{ $kelompok->jenis_produk_pinjaman != '3' ? 'Kelompok' : 'Lembaga Usaha' }}
                    </div>
                    <div class="row">
                        @if ($kelompok->jenis_produk_pinjaman != '3')
                            <div class="col-md-4">
                                <div class="input-group input-group-static my-3">
                                    <label for="ketua">Ketua</label>
                                    <input autocomplete="off" type="text" name="ketua" id="ketua"
                                        class="form-control" value="{{ $kelompok->ketua }}">
                                    <small class="text-danger" id="msg_ketua"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-static my-3">
                                    <label for="sekretaris">Sekretaris</label>
                                    <input autocomplete="off" type="text" name="sekretaris" id="sekretaris"
                                        class="form-control" value="{{ $kelompok->sekretaris }}">
                                    <small class="text-danger" id="msg_sekretaris"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-static my-3">
                                    <label for="bendahara">Bendahara</label>
                                    <input autocomplete="off" type="text" name="bendahara" id="bendahara"
                                        class="form-control" value="{{ $kelompok->bendahara }}">
                                    <small class="text-danger" id="msg_bendahara"></small>
                                </div>
                            </div>
                        @else
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="pimpinan">Pimpinan</label>
                                    <input autocomplete="off" type="text" name="pimpinan" id="pimpinan"
                                        class="form-control" value="{{ $kelompok->ketua }}">
                                    <small class="text-danger" id="msg_pimpinan"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="penanggung_jawab">Penanggung Jawab</label>
                                    <input autocomplete="off" type="text" name="penanggung_jawab"
                                        id="penanggung_jawab" class="form-control"
                                        value="{{ $kelompok->sekretaris }}">
                                    <small class="text-danger" id="msg_penanggung_jawab"></small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <button type="submit" id="SimpanProposal" class="btn btn-github btn-sm float-end">Simpan Proposal</button>
    </div>
</div>

<script>
    new Choices($('#jenis_jasa')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
    new Choices($('#sistem_angsuran_pokok')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
    new Choices($('#sistem_angsuran_jasa')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
    new Choices($('#jenis_produk_pinjaman')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
    new Choices($('#pembulatan')[0], { shouldSort: false })
    new Choices($('#sistem_pembulatan')[0], { shouldSort: false })

    $("#pengajuan").maskMoney();

    $(".date").flatpickr({
        dateFormat: "d/m/Y"
    })
</script>
