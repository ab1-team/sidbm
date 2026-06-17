<form id="formLicense" data-mode="{{ $mode }}">
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
        <input type="hidden" name="id" value="{{ $license->id }}">
    @endif

    <div class="row">
        <div class="col-md-12">
            @if ($mode === 'edit')
                {{-- Kecamatan terikat ke license; tidak diubah saat edit. Tampilkan sebagai info. --}}
                <input type="hidden" name="kecamatan_id" value="{{ $license->kecamatan_id }}">
                <div class="input-group input-group-static my-3">
                    <label>Kecamatan</label>
                    <input type="text" class="form-control" value="{{ optional($license->kecamatan)->label ?? $license->kecamatan_id }}" readonly>
                    <small class="text-muted">Kecamatan tidak dapat diubah. Hapus license & buat baru untuk pindah kecamatan.</small>
                </div>
            @else
                <div class="input-group input-group-static my-3">
                    <label for="kecamatan_id">Kecamatan</label>
                    <select name="kecamatan_id" id="kecamatan_id" class="form-control" required>
                        <option value="">-- Pilih Kecamatan --</option>
                        @foreach ($kecamatan as $k)
                            <option value="{{ $k->id }}" {{ ($license->kecamatan_id ?? '') == $k->id ? 'selected' : '' }}>
                                {{ $k->label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-danger" id="msg_kecamatan_id"></small>
                </div>
            @endif
        </div>

        <div class="col-md-12">
            <div class="input-group input-group-static my-3">
                <label for="api_secret">API Secret</label>
                <input autocomplete="off" type="text" name="api_secret" id="api_secret"
                    class="form-control" value="{{ $license->api_secret }}" required>
                <small class="text-muted">
                    Dapatkan dari aplikasi level pusat. Setiap kecamatan memiliki api_secret unik.
                </small>
                <small class="text-danger" id="msg_api_secret"></small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="my-3">
                <label for="is_active" class="form-label">Status Aktif</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $license->is_active ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Aktifkan license</label>
                </div>
                <small class="text-danger" id="msg_is_active"></small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="input-group input-group-static my-3">
                <label for="expired_at">Tanggal Berakhir</label>
                <input autocomplete="off" type="text" name="expired_at" id="expired_at"
                    class="form-control date"
                    value="{{ $license->expired_at ? $license->expired_at->format('Y-m-d H:i') : '' }}">
                <small class="text-muted">Kosongkan jika tidak ada batas waktu.</small>
                <small class="text-danger" id="msg_expired_at"></small>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12 text-end">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-github btn-sm">
                {{ $mode === 'edit' ? 'Perbarui' : 'Simpan' }}
            </button>
        </div>
    </div>
</form>
