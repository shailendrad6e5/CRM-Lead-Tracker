<nav id="sidebar" class="bg-white border-end vh-100 position-sticky top-0 p-3" style="min-width: 250px; transition: all 0.3s;">
    <div class="sidebar-header d-flex justify-content-between align-items-center mb-4">
        <h3 class="fs-5 fw-bold m-0 text-primary">
            <i class="bi bi-hexagon-fill me-2"></i>Mini CRM
        </h3>
        <button id="sidebarCollapseBtn" class="btn btn-sm d-md-none">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $in_leads = strpos($_SERVER['REQUEST_URI'], '/leads/') !== false;
    ?>

    <ul class="nav flex-column gap-2">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'dashboard.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/leads/list.php" class="nav-link px-3 py-2 rounded <?= ($in_leads && $current_page != 'add.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-people me-2"></i> Leads
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/followups.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'followups.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-calendar-check me-2"></i> Follow-ups
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/leads/add.php" class="nav-link px-3 py-2 rounded <?= ($in_leads && $current_page == 'add.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-person-plus me-2"></i> Add Lead
            </a>
        </li>
        <li class="nav-item mt-auto pt-4 border-top">
            <a href="<?= BASE_URL ?>/profile.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'profile.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-person-circle me-2"></i> Profile
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link px-3 py-2 rounded text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
</nav>
