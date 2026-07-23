<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3 sticky-top">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3">
            <i class="bi bi-list fs-5"></i>
        </button>

        <h4 class="m-0 fw-semibold d-none d-md-block"><?= isset($pageTitle) ? $pageTitle : 'Dashboard' ?></h4>

        <div class="ms-auto d-flex align-items-center gap-3">
            <form class="d-none d-lg-flex position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="search" class="form-control rounded-pill ps-5 bg-light border-0" placeholder="Search leads..." style="width: 250px;">
            </form>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-person fs-5"></i>
                    </div>
                    <span class="d-none d-md-inline">Admin</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
