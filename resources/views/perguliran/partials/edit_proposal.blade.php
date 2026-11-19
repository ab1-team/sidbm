@php
    $ketua = $perguliran->kelompok->ketua;
    $sekretaris = $perguliran->kelompok->sekretaris;
    $bendahara = $perguliran->kelompok->bendahara;
    if ($perguliran->struktur_kelompok) {
        $struktur_kelompok = json_decode($perguliran->struktur_kelompok, true);
        $ketua = $struktur_kelompok['ketua'] ?? '';
        $sekretaris = $struktur_kelompok['sekretaris'] ?? '';
        $bendahara = $struktur_kelompok['bendahara'] ?? '';
    }

    // Pembulatan per pinjaman: pecah format legacy ('+1000' / '-500' / '500').
    $bul_nilai = (string) ($perguliran->pembulatan ?? '');
    $bul_sistem = '';
    if (\App\Utils\Keuangan::startWith($bul_nilai, '+')) {
        $bul_sistem = '+';
        $bul_nilai = substr($bul_nilai, 1);
    } elseif (\App\Utils\Keuangan::startWith($bul_nilai, '-')) {
        $bul_sistem = '-';
        $bul_nilai = substr($bul_nilai, 1);
    }
@endphp

<form action="/perguliran/{{ $perguliran->id }}" method="post" id="FormEditProposal">
    @csrf
    @method('PUT')

    <input type="hidden" name="_id" id="_id" value="{{ $perguliran->id }}">
    <input type="hidden" name="status" id="status" value="P">
    <div class="row">
        <div class="col-md-3">
            <div class="input-group input-group-static my-3">
                <label for="tgl_verifikasi">Tgl Proposal</label>
                <input autocomplete="off" type="text" name="tgl_proposal" id="tgl_proposal" class="form-control date"
                    value="{{ Tanggal::tglIndo($perguliran->tgl_proposal) }}">
                <small class="text-danger" id="msg_tgl_proposal"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group input-group-static my-3">
                <label for="proposal">Pengajuan Rp.</label>
                <input autocomplete="off" type="text" name="proposal" id="proposal" class="form-control money"
                    value="{{ number_format($perguliran->proposal, 2) }}">
                <small class="text-danger" id="msg_proposal"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group input-group-static my-3">
                <label for="jangka_proposal">Jangka</label>
                <input autocomplete="off" type="number" name="jangka_proposal" id="jangka_proposal"
                    class="form-control" value="{{ $perguliran->jangka }}">
                <small class="text-danger" id="msg_jangka_proposal"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group input-group-static my-3">
                <label for="pros_jasa_proposal">Prosentase Jasa (%)</label>
                <input autocomplete="off" type="number" name="pros_jasa_proposal" id="pros_jasa_proposal"
                    class="form-control" value="{{ $perguliran->pros_jasa }}">
                <small class="text-danger" id="msg_pros_jasa_proposal"></small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="my-2">
                <label class="form-label" for="jenis_jasa_proposal">Jenis Jasa</label>
                <select class="form-control" name="jenis_jasa_proposal" id="jenis_jasa_proposal">
                    @foreach ($jenis_jasa as $jj)
                        <option {{ $jj->id == $perguliran->jenis_jasa ? 'selected' : '' }} value="{{ $jj->id }}">
                            {{ $jj->nama_jj }}
                        </option>
                    @endforeach
                </select>
                <small class="text-danger" id="msg_jenis_jasa_proposal"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="my-2">
                <label class="form-label" for="jenis_produk_pinjaman">Jenis Produk Pinjaman</label>
                <select class="form-control" name="jenis_produk_pinjaman" id="jenis_produk_pinjaman">
                    @foreach ($jenis_pp as $jpp)
                        <option {{ $jenis_pp_dipilih == $jpp->id ? 'selected' : '' }} value="{{ $jpp->id }}">
                            {{ $jpp->nama_jpp }} ({{ $jpp->deskripsi_jpp }})
                        </option>
                    @endforeach
                </select>
                <small class="text-danger" id="msg_jenis_produk_pinjaman"></small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="my-2">
                <label class="form-label" for="sistem_angsuran_pokok_proposal">Sistem Angs. Pokok</label>
                <select class="form-control" name="sistem_angsuran_pokok_proposal" id="sistem_angsuran_pokok_proposal">
                    @foreach ($sistem_angsuran as $sa)
                        <option {{ $sa->id == $perguliran->sistem_angsuran ? 'selected' : '' }}
                            value="{{ $sa->id }}">
                            {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                        </option>
                    @endforeach
                </select>
                <small class="text-danger" id="msg_sistem_angsuran_pokok_proposal"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="my-2">
                <label class="form-label" for="sistem_angsuran_jasa_proposal">Sistem Angs. Jasa</label>
                <select class="form-control" name="sistem_angsuran_jasa_proposal" id="sistem_angsuran_jasa_proposal">
                    @foreach ($sistem_angsuran as $sa)
                        <option {{ $sa->id == $perguliran->sa_jasa ? 'selected' : '' }} value="{{ $sa->id }}">
                            {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                        </option>
                    @endforeach
                </select>
                <small class="text-danger" id="msg_sistem_angsuran_jasa_proposal"></small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="my-2">
                <label class="form-label" for="pembulatan">Pembulatan (opsional)</label>
                <select class="form-control" name="pembulatan" id="pembulatan">
                    <option value="" {{ $bul_nilai === '' ? 'selected' : '' }}>Ikuti pengaturan kecamatan</option>
                    <option value="500" {{ $bul_nilai === '500' ? 'selected' : '' }}>500</option>
                    <option value="1000" {{ $bul_nilai === '1000' ? 'selected' : '' }}>1000</option>
                </select>
                <small class="form-text">Berlaku hanya untuk pinjaman ini.</small>
                <small class="text-danger" id="msg_pembulatan"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="my-2">
                <label class="form-label" for="sistem_pembulatan">Sistem Pembulatan</label>
                <select class="form-control" name="sistem_pembulatan" id="sistem_pembulatan">
                    <option value="" {{ $bul_sistem === '' ? 'selected' : '' }}>Auto</option>
                    <option value="+" {{ $bul_sistem === '+' ? 'selected' : '' }}>Keatas</option>
                    <option value="-" {{ $bul_sistem === '-' ? 'selected' : '' }}>Kebawah</option>
                </select>
                <small class="text-danger" id="msg_sistem_pembulatan"></small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            <div class="text-center fw-bold">
                Struktur Kelompok
            </div>

            @php
                $class = 'col-md-4';
                if ($perguliran->jenis_pp == 3) {
                    $class = 'col-md-6';
                }
            @endphp

            <div class="row">
                <div class="{{ $class }}">
                    <div class="input-group input-group-static my-3">
                        <label for="ketua">Ketua</label>
                        <input autocomplete="off" type="text" name="ketua" id="ketua" class="form-control"
                            value="{{ $ketua }}">
                        <small class="text-danger" id="msg_ketua"></small>
                    </div>
                </div>
                <div class="{{ $class }}">
                    <div class="input-group input-group-static my-3">
                        <label for="sekretaris">Sekretaris</label>
                        <input autocomplete="off" type="text" name="sekretaris" id="sekretaris"
                            class="form-control" value="{{ $sekretaris }}">
                        <small class="text-danger" id="msg_sekretaris"></small>
                    </div>
                </div>
                @if ($perguliran->jenis_pp != 3)
                    <div class="{{ $class }}">
                        <div class="input-group input-group-static my-3">
                            <label for="bendahara">Bendahara</label>
                            <input autocomplete="off" type="text" name="bendahara" id="bendahara"
                                class="form-control" value="{{ $bendahara }}">
                            <small class="text-danger" id="msg_bendahara"></small>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="bendahara" id="bendahara" value="{{ $bendahara }}">
                @endif
            </div>
        </div>
    </div>
</form>

<script>
    new Choices($('#jenis_jasa_proposal')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
    new Choices($('#sistem_angsuran_pokok_proposal')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
    new Choices($('#sistem_angsuran_jasa_proposal')[0], {
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

    $(".money").maskMoney();

    $(".date").flatpickr({
        dateFormat: "d/m/Y"
    })
</script>
