@php
    use App\Utils\Tanggal;

    $perguliran = $kelompok->pinjaman;
@endphp

<div class="card">
    <div class="card-header p-3 pt-2">
        <div class="alert alert-warning text-white shadow text-center border-radius-xl mt-n4 me-3">
            Kelompok <b>{{ $kelompok->nama_kelompok }}</b> masih memiliki pinjaman dengan status
            <b>{{ $perguliran->sts->nama_status }} ({{ $perguliran->status }})</b>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row mt-0">
            @if ($perguliran->status == 'P')
                <div class="col-md-6 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-info text-gradient mb-0">
                            Proposal
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Tgl Pengajuan
                                <span class="badge badge-info badge-pill">
                                    {{ Tanggal::tglIndo($perguliran->tgl_proposal) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Pengajuan
                                <span class="badge badge-info badge-pill">
                                    {{ number_format($perguliran->proposal) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jenis Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->jasa->nama_jj }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-info text-gradient mb-0">
                            &nbsp;
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Pokok
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->sis_pokok->nama_sistem }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->sis_jasa->nama_sistem }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            @elseif ($perguliran->status == 'V')
                <div class="col-md-6 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-info text-gradient mb-0">
                            Proposal
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Tgl Pengajuan
                                <span class="badge badge-info badge-pill">
                                    {{ Tanggal::tglIndo($perguliran->tgl_proposal) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Pengajuan
                                <span class="badge badge-info badge-pill">
                                    {{ number_format($perguliran->proposal) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jenis Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->jasa->nama_jj }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Pokok
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->sis_pokok->nama_sistem }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->sis_jasa->nama_sistem }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-danger text-gradient mb-0">
                            Verified
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Tgl Verifikasi
                                <span class="badge badge-danger badge-pill">
                                    {{ Tanggal::tglIndo($perguliran->tgl_verifikasi) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Verifikasi
                                <span class="badge badge-danger badge-pill">
                                    {{ number_format($perguliran->verifikasi) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jenis Jasa
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->jasa->nama_jj }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Pokok
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->sis_pokok->nama_sistem }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Jasa
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->sis_jasa->nama_sistem }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="col-md-4 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-info text-gradient mb-0">
                            Proposal
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Tgl Pengajuan
                                <span class="badge badge-info badge-pill">
                                    {{ Tanggal::tglIndo($perguliran->tgl_proposal) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Pengajuan
                                <span class="badge badge-info badge-pill">
                                    {{ number_format($perguliran->proposal) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jenis Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->jasa->nama_jj }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Pokok
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->sis_pokok->nama_sistem }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->sis_jasa->nama_sistem }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-danger text-gradient mb-0">
                            Verified
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Tgl Verifikasi
                                <span class="badge badge-danger badge-pill">
                                    {{ Tanggal::tglIndo($perguliran->tgl_verifikasi) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Verifikasi
                                <span class="badge badge-danger badge-pill">
                                    {{ number_format($perguliran->verifikasi) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jenis Jasa
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->jasa->nama_jj }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Pokok
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->sis_pokok->nama_sistem }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Jasa
                                <span class="badge badge-danger badge-pill">
                                    {{ $perguliran->sis_jasa->nama_sistem }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-warning text-gradient mb-0">
                            Waiting
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Tgl Tunggu
                                <span class="badge badge-warning badge-pill">
                                    {{ Tanggal::tglIndo($perguliran->tgl_tunggu) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Pendanaan
                                <span class="badge badge-warning badge-pill">
                                    {{ number_format($perguliran->alokasi) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jenis Jasa
                                <span class="badge badge-warning badge-pill">
                                    {{ $perguliran->jasa->nama_jj }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-warning badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Pokok
                                <span class="badge badge-warning badge-pill">
                                    {{ $perguliran->sis_pokok->nama_sistem }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Angs. Jasa
                                <span class="badge badge-warning badge-pill">
                                    {{ $perguliran->sis_jasa->nama_sistem }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="d-flex justify-content-end">
            <a href="detail/{{ $perguliran->id }}" class="btn btn-info btn-sm mb-0">Detail Pinjaman</a>
        </div>
    </div>
</div>
