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
    $in_leads     = strpos($_SERVER['REQUEST_URI'], '/leads/') !== false;
    $in_team      = strpos($_SERVER['REQUEST_URI'], '/team') !== false;
    $userRole     = getUserRole();
    ?>

    <ul class="nav flex-column gap-1">

        <!-- Dashboard — All Roles -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'dashboard.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <!-- My Leads — All Roles -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/my_leads.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'my_leads.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-person-lines-fill me-2"></i> My Leads
            </a>
        </li>

        <!-- All Leads — Admin & Manager only -->
        <?php if (hasAnyRole(['admin','manager'])): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/leads/list.php" class="nav-link px-3 py-2 rounded <?= ($in_leads && !in_array($current_page, ['add.php'])) ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-people me-2"></i> Company Leads
            </a>
        </li>
        <?php endif; ?>

        <!-- Follow-ups — All Roles -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/followups.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'followups.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-calendar-check me-2"></i> Follow-ups
            </a>
        </li>

        <!-- Add Lead — All Roles -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/leads/add.php" class="nav-link px-3 py-2 rounded <?= ($in_leads && $current_page == 'add.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-person-plus me-2"></i> Add Lead
            </a>
        </li>

        <!-- Team Management — Admin & Manager -->
        <?php if (hasAnyRole(['admin', 'manager'])): ?>
        <li class="nav-item mt-2">
            <div class="px-3 py-1">
                <span class="text-muted" style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Administration</span>
            </div>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/team.php" class="nav-link px-3 py-2 rounded <?= ($in_team && $current_page !== 'notifications.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-people-fill me-2"></i> Team Management
            </a>
        </li>
        <?php endif; ?>

        <!-- Separator -->
        <li class="nav-item mt-auto pt-3 border-top">
        </li>

        <!-- Profile — All Roles -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/profile.php" class="nav-link px-3 py-2 rounded <?= ($current_page == 'profile.php') ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="bi bi-person-circle me-2"></i> Profile
            </a>
        </li>

        <!-- Logout -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link px-3 py-2 rounded text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
</nav>
