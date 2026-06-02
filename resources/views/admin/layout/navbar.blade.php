<nav class="navbar navbar-main navbar-expand-lg position-sticky mt-4 top-1 px-0 mx-4 shadow-none border-radius-xl z-index-sticky"
    id="navbarBlur" data-scroll="true">
    <div class="container-fluid py-1 px-3">
        <div class="sidenav-toggler sidenav-toggler-inner d-xl-block d-none ">
            <a href="javascript:;" class="nav-link text-body p-0" onclick="navbarMinimize(this)">
                <div class="sidenav-toggler-inner">
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                </div>
            </a>
        </div>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 justify-content-between" id="navbar">
            <div class="ms-md-3 pe-md-3 d-flex align-items-center w-100">

            </div>
            <ul class="navbar-nav justify-content-end align-items-center">
                <li class="nav-item ps-3">
                    <select class="form-select form-select-sm" id="master-server-switcher"
                        style="min-width: 120px; font-weight: bold;">
                        <option value="mysql_b"
                            {{ session('master_server') == 'mysql_b' || !session()->has('master_server') ? 'selected' : '' }}>
                            Server SIUPK</option>
                        <option value="mysql" {{ session('master_server') == 'mysql' ? 'selected' : '' }}>Server SIDBM
                        </option>
                    </select>
                </li>
                <li class="nav-item ps-3">
                    <a href="javascript:;" class="nav-link text-body p-0">
                        <i class="material-icons fixed-plugin-button-nav cursor-pointer">
                            settings
                        </i>
                    </a>
                </li>
                <li class="nav-item ps-3">
                    <a href="javascript:;" class="nav-link text-body p-0" id="logout">
                        <i class="material-icons fixed-plugin-button-nav cursor-pointer">
                            exit_to_app
                        </i>
                    </a>
                </li>
                <li class="nav-item d-xl-none d-flex align-items-center ps-3">
                    <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
