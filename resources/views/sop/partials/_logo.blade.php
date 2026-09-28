<div class="row">
    <div class="col-md-6">
        <div class="card mt-4 border" data-animation="true">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <a class="d-block blur-shadow-image">
                    <img src="{{ $kec->logo ?: Session::get('logo') }}" alt="img-blur-shadow" class="img-fluid shadow border-radius-lg"
                        id="previewLogo">
                </a>
                <div class="colored-shadow" style="background-image: url(&quot;{{ $kec->logo ?: Session::get('logo') }}&quot;);">
                </div>
            </div>
            <div class="card-body text-center pb-0">
                <div class="d-flex mt-n6 justify-content-center">
                    <button class="btn btn-link text-info border-0" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-original-title="Edit" id="EditLogo">
                        <i class="material-icons text-lg">edit</i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="/pengaturan/logo/{{ $kec->id }}" method="post" enctype="multipart/form-data" id="FormLogo">
    @csrf
    @method('PUT')

    <input type="file" name="logo_kec" id="logo_kec" class="d-none">
</form>
