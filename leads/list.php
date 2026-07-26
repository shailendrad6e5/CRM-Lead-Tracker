<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();

$pageTitle = 'Leads';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    verifyCsrfToken();
    $id   = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ? AND assigned_to = ?");
    if ($stmt->execute([$id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Lead deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete lead.";
    }
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

// Build query with filters and search
$where  = "assigned_to = ?";
$params = [$_SESSION['user_id']];

$search   = trim($_GET['search']   ?? '');
$fStatus  = $_GET['status']        ?? '';
$fPriority= $_GET['priority']      ?? '';
$fSource  = $_GET['source']        ?? '';
$fDateFrom= $_GET['date_from']     ?? '';
$fDateTo  = $_GET['date_to']       ?? '';

if (!empty($search)) {
    $where   .= " AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if (!empty($fStatus)) {
    $where   .= " AND status = ?";
    $params[] = $fStatus;
}
if (!empty($fPriority)) {
    $where   .= " AND priority = ?";
    $params[] = $fPriority;
}
if (!empty($fSource)) {
    $where   .= " AND source = ?";
    $params[] = $fSource;
}
if (!empty($fDateFrom)) {
    $where   .= " AND DATE(created_at) >= ?";
    $params[] = $fDateFrom;
}
if (!empty($fDateTo)) {
    $where   .= " AND DATE(created_at) <= ?";
    $params[] = $fDateTo;
}

// Whitelist sort columns
$allowedSorts = ['created_at', 'name', 'company', 'status', 'priority'];
$orderBy  = in_array($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'created_at';
$orderDir = (strtolower($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';
$nextDir  = ($orderDir === 'ASC') ? 'desc' : 'asc';

// Rows per page
$allowedLimits = [10, 25, 50, 100];
$limit = in_array((int)($_GET['per_page'] ?? 10), $allowedLimits) ? (int)$_GET['per_page'] : 10;

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Build filter query string for pagination links
$filterParams = http_build_query(array_filter([
    'search'    => $search,
    'status'    => $fStatus,
    'priority'  => $fPriority,
    'source'    => $fSource,
    'date_from' => $fDateFrom,
    'date_to'   => $fDateTo,
    'sort'      => $orderBy,
    'dir'       => strtolower($orderDir),
    'per_page'  => $limit !== 10 ? $limit : '',
]));

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE $where");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Fetch data
$sql  = "SELECT * FROM leads WHERE $where ORDER BY $orderBy $orderDir LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Leads</h1>
        <p class="text-muted small mb-0">Manage and track your potential customers</p>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Lead
    </a>
</div>

<div class="card p-0 mb-4">
    <div class="card-header bg-light">
        <form action="" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Name, company, email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach(['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $s): ?>
                        <option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <?php foreach(['High','Medium','Low'] as $p): ?>
                        <option value="<?= $p ?>" <?= $fPriority===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Source</label>
                    <select name="source" class="form-select">
                        <option value="">All Sources</option>
                        <?php foreach(['Website','Referral','Cold Call','Email Campaign','Other'] as $src): ?>
                        <option value="<?= $src ?>" <?= $fSource===$src?'selected':'' ?>><?= $src ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">Per Page</label>
                    <select name="per_page" class="form-select">
                        <?php foreach([10,25,50,100] as $pp): ?>
                        <option value="<?= $pp ?>" <?= $limit===$pp?'selected':'' ?>><?= $pp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter me-1"></i>Filter</button>
                    <a href="list.php" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
            <!-- Date range row -->
            <div class="row g-2 mt-1">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fDateFrom) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($fDateTo) ?>">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <small class="text-muted"><?= $totalRecords ?> lead<?= $totalRecords!=1?'s':'' ?> found<?= !empty($filterParams) ? ' (filtered)' : '' ?></small>
                </div>
            </div>
        </form>
    </div>
                <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <?php
                        function sortLink($col, $label, $currentSort, $currentDir, $filterParams) {
                            $dir = ($currentSort === $col && $currentDir === 'ASC') ? 'desc' : 'asc';
                            $icon = ($currentSort === $col) ? ($currentDir === 'ASC' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
                            $qs = $filterParams ? $filterParams . '&' : '';
                            return "<th><a href=\"?{$qs}sort={$col}&dir={$dir}\" class=\"text-decoration-none text-dark fw-semibold\">{$label} <i class=\"bi {$icon} small ms-1\"></i></a></th>";
                        }
                        echo sortLink('name',       'Name',     $orderBy, $orderDir, $filterParams);
                        echo sortLink('company',    'Company',  $orderBy, $orderDir, $filterParams);
                        ?>
                        <th>Contact</th>
                        <?php
                        echo sortLink('status',   'Status',   $orderBy, $orderDir, $filterParams);
                        echo sortLink('priority', 'Priority', $orderBy, $orderDir, $filterParams);
                        echo sortLink('created_at','Date',    $orderBy, $orderDir, $filterParams);
                        ?>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="fs-5 fw-semibold mt-3 mb-1">No leads found</p>
                                <p class="text-muted small mb-3">Try adjusting your search or filters, or add a new lead.</p>
                                <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-2"></i>Add First Lead</a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($leads as $lead): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none d-block"><?= htmlspecialchars($lead['name']) ?></a>
                            </td>
                            <td>
                                <div class="small text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($lead['company'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['email'] ?? 'N/A') ?></div>
                                <div class="small"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($lead['phone'] ?? 'N/A') ?></div>
                            </td>
                            <td><span class="badge <?= getStatusBadgeClass($lead['status']) ?>"><?= $lead['status'] ?></span></td>
                            <td><span class="badge <?= getPriorityBadgeClass($lead['priority']) ?>"><?= $lead['priority'] ?></span></td>
                            <td><span class="small"><?= date('M d, Y', strtotime($lead['created_at'])) ?></span></td>
                            <td class="text-end">
                                <a href="view.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-primary border me-1" title="View"><i class="bi bi-eye"></i></a>
                                <a href="edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-warning border me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn btn-sm btn-light text-danger border" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $lead['id'] ?>" data-url="<?= BASE_URL ?>/leads/list.php">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="card-footer bg-white border-top py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$limit, $totalRecords) ?> of <?= $totalRecords ?> leads</small>
            <nav>
                <ul class="pagination mb-0 pagination-sm">
                    <li class="page-item <?= ($page<=1)?'disabled':'' ?>">
                        <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $page-1 ?>">«</a>
                    </li>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage   = min($totalPages, $page + 2);
                    if ($startPage > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?= ($page==$i)?'active':'' ?>">
                        <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php
                    endfor;
                    if ($endPage < $totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    ?>
                    <li class="page-item <?= ($page>=$totalPages)?'disabled':'' ?>">
                        <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $page+1 ?>">»</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
