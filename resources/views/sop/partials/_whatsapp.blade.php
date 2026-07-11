@php
    $pesan_wa = json_decode($kec->whatsapp, true);
    if (!$pesan_wa) {
        $pesan_wa = [
            'tagihan' => '',
            'angsuran' => ''
        ];
    }
@endphp

<form action="/pengaturan/pesan_whatsapp/{{ $kec->id }}" method="post" id="FormScanWhatsapp">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-6">
            <div class="input-group input-group-static mb-3">
                <label for="tagihan">Pesan Tagihan</label>
                <textarea class="form-control" name="tagihan" id="tagihan" cols="20" rows="10">{!! $pesan_wa['tagihan'] !!}</textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="input-group input-group-static mb-3">
                <label for="angsuran">Pesan Angsuran</label>
                <textarea class="form-control" name="angsuran" id="angsuran" cols="20" rows="10">{!! $pesan_wa['angsuran'] !!}</textarea>
            </div>
        </div>
    </div>
</form>

<div class="d-flex justify-content-end align-items-center">
    <small id="InstanceInfo" class="text-muted me-3" style="display: none;">
        Instance: <code id="InstanceName"></code>
    </small>

    <button type="button" id="HapusWa" class="btn btn-sm btn-danger mb-0 me-2" style="display: none;">
        Hapus Whatsapp
    </button>
    <button type="button" id="ScanWA" class="btn btn-sm btn-info mb-0 me-2" style="display: none;">
        Scan Whatsapp
    </button>
    <button type="button" id="PairWA" class="btn btn-sm btn-warning mb-0 me-2" style="display: none;">
        Pair via Nomor
    </button>
    <button type="button" id="CreateInstance" class="btn btn-sm btn-primary mb-0 me-2" style="display: none;">
        Buat Instance
    </button>

    <button type="button" id="SimpanWhatsapp" data-target="#FormScanWhatsapp"
        class="btn btn-sm btn-github mb-0 btn-simpan">
        Simpan Perubahan
    </button>
</div>
