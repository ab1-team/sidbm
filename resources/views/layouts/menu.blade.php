@foreach ($parent_menu as $menu)
    @if (count($menu->child) > 0)
        @php
            $link = [];
            $path = '/' . Request::path();

            $links = $menu->pluck('link');
            $links->each(function ($menu_link) {
                $link[] = $menu_link;
            });

            $active = '';
            if (in_array($path, $link)) {
                $active = 'active';
            }

            if (in_array(str_replace('#', '', $menu->link), explode('/', $path))) {
                $active = 'active';
            }

            foreach ($menu->child as $child) {
                if ($active == 'active') {
                    break;
                }

                $child_link = explode('/', $child);
                if (in_array(Request::path(), $child_link)) {
                    $active = 'active';
                }
            }
        @endphp
        <li class="nav-item">
            <a data-bs-toggle="collapse" href="#menu_{{ str_replace('#', '', $menu->link) }}"
                class="nav-link text-white {{ $active }}"
                aria-controls="menu_{{ str_replace('#', '', $menu->link) }}" role="button" aria-expanded="false">
                @if (isset($is_sub))
                    <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                @elseif ($menu->type == 'material')
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">{{ $menu->ikon }}</i>
                    </div>
                @else
                    <span class="sidenav-mini-icon"> {{ $menu->ikon }} </span>
                @endif
                <span class="nav-link-text ms-1">{{ $menu->title }}</span>
            </a>
            <div class="collapse" id="menu_{{ str_replace('#', '', $menu->link) }}">
                <ul class="nav nav-sm flex-column ms-3">
                    @include('layouts.menu', ['parent_menu' => $menu->child, 'is_sub' => true])
                </ul>
            </div>
        </li>
    @else
        @if ($menu->link == '' && $menu->type == '' && $menu->ikon == '')
            <li class="nav-item mt-3">
                <h6 class="ps-4  ms-2 text-uppercase text-xs font-weight-bolder text-white">{{ $menu->title }}</h6>
            </li>
        @else
            @php
                $path = '/' . Request::path();

                $arr_path = explode('/', $path);
                $arr_menu = explode('/', $menu->link);

                $end_page = end($arr_path);
                $end_menu_link = end($arr_menu);

                $active = '';
                if ($path == $menu->link || $end_page == $end_menu_link) {
                    $active = 'active';
                }

                if ((in_array('detail', $arr_path) || in_array('lunas', $arr_path)) && $menu->link == '/perguliran') {
                    $active = 'active';
                }
            @endphp
            <li class="nav-item nav-item-link {{ $active }}">
                <a class="nav-link text-white {{ $active }}" href="{{ $menu->link }}">
                    @if (isset($is_sub))
                        <span class="sidenav-mini-icon"> <i class="fas fa-circle" style="font-size: 8px;"></i> </span>
                    @elseif ($menu->type == 'material')
                        <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                            <i class="material-icons opacity-10">{{ $menu->ikon }}</i>
                        </div>
                    @else
                        <span class="sidenav-mini-icon"> {{ $menu->ikon }} </span>
                    @endif
                    <span class="nav-link-text ms-1">{{ $menu->title }}</span>
                </a>
            </li>
        @endif
    @endif
@endforeach
