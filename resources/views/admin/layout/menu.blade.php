@php
    $path = Request::path();
    $path = explode('/', $path);
@endphp

@if ($title == 'Provinsi')
    @foreach ($wilayah as $prov)
        @php
            $active = '';
            if (in_array($prov->kode, $path)) {
                $active = 'active';
            }
        @endphp
        @if ($prov->kab_count > 0)
            <li class="nav-item nav-item-link {{ $active }}">
                <a class="nav-link text-white {{ $active }}" href="/master/provinsi/{{ $prov->kode }}">
                    <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                    <span class="nav-link-text ms-1">
                        {{ ucwords(strtolower($prov->nama)) }}
                    </span>
                </a>
            </li>
        @endif
    @endforeach
@elseif ($title == 'Kabupaten')
    @foreach ($wilayah as $prov)
        @php
            $active = '';
            if (in_array($prov->kode, $path)) {
                $active = 'active';
            }
        @endphp
        @if ($prov->kab_count > 0)
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#Menu{{ $prov->kode }}"
                    class="nav-link text-white {{ $active }}" aria-controls="Menu{{ $prov->kode }}" role="button"
                    aria-expanded="false">
                    <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                    <span class="nav-link-text ms-1">
                        {{ ucwords(strtolower($prov->nama)) }}
                    </span>
                </a>
                <div class="collapse" id="Menu{{ $prov->kode }}">
                    <ul class="nav nav-sm flex-column ms-3">
                        @foreach ($prov->kab as $kab)
                            @php
                                $active = '';
                                if (in_array($kab->kd_kab, $path)) {
                                    $active = 'active';
                                }
                            @endphp
                            @if ($kab->kec_count)
                                <li class="nav-item">
                                    <a class="nav-link text-white {{ $active }}"
                                        href="/master/kabupaten/{{ $prov->kode }}/{{ $kab->kd_kab }}">
                                        <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                                        <span class="sidenav-normal  ms-2  ps-1">
                                            {{ ucwords(strtolower($kab->nama_kab)) }}
                                        </span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </li>
        @endif
    @endforeach
@else
    @foreach ($wilayah as $prov)
        @php
            $active = '';
            if (in_array($prov->kode, $path)) {
                $active = 'active';
            }
        @endphp
        @if ($prov->kab_count > 0)
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#Menu{{ $prov->kode }}"
                    class="nav-link text-white {{ $active }}" aria-controls="Menu{{ $prov->kode }}"
                    role="button" aria-expanded="false">
                    <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                    <span class="nav-link-text ms-1">
                        {{ ucwords(strtolower($prov->nama)) }}
                    </span>
                </a>
                <div class="collapse" id="Menu{{ $prov->kode }}">
                    <ul class="nav nav-sm flex-column ms-3">
                        @foreach ($prov->kab as $kab)
                            @php
                                $active = '';
                                if (in_array($kab->kd_kab, $path)) {
                                    $active = 'active';
                                }
                            @endphp
                            @if ($kab->kec_count)
                                <li class="nav-item">
                                    <a data-bs-toggle="collapse" href="#Menu{{ str_replace('.', '', $kab->kd_kab) }}"
                                        class="nav-link text-white {{ $active }}"
                                        aria-controls="Menu{{ str_replace('.', '', $kab->kd_kab) }}" role="button"
                                        aria-expanded="false">
                                        <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                                        <span class="nav-link-text ms-1">
                                            {{ ucwords(strtolower($kab->nama_kab)) }}
                                        </span>
                                    </a>
                                    <div class="collapse" id="Menu{{ str_replace('.', '', $kab->kd_kab) }}">
                                        <ul class="nav nav-sm flex-column ms-3">
                                            @foreach ($kab->kec as $kec)
                                                @php
                                                    $active = '';
                                                    if (in_array($kec->kd_kec, $path)) {
                                                        $active = 'active';
                                                    }
                                                @endphp
                                                <li class="nav-item">
                                                    <a class="nav-link text-white {{ $active }}"
                                                        href="/master/kecamatan/{{ $prov->kode }}/{{ $kab->kd_kab }}/{{ $kec->kd_kec }}">
                                                        <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                                                        <span class="nav-link-text ms-1">
                                                            {{ ucwords(strtolower($kec->nama_kec)) }}
                                                        </span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </li>
        @endif
    @endforeach
@endif
