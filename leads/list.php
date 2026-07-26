<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

$pageTitle = 'Leads';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ? AND assigned_to = ?");
    if($stmt->execute([$id, $_SESSION['user_id']])) {
        $_SESSION['success'] = "Lead deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete lead.";
    }
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

// Build query with filters and search
$where = "assigned_to = ?";
$params = [$_SESSION['user_id']];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where .= " AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $where .= " AND priority = ?";
    $params[] = $_GET['priority'];
}

$orderBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$orderDir = isset($_GET['dir']) && strtolower($_GET['dir']) == 'asc' ? 'ASC' : 'DESC';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE $where");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch data
$sql = "SELECT * FROM leads WHERE $where ORDER BY $orderBy $orderDir LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
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
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, company, email..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="New" <?= (isset($_GET['status']) && $_GET['status'] == 'New') ? 'selected' : '' ?>>New</option>
                    <option value="Contacted" <?= (isset($_GET['status']) && $_GET['status'] == 'Contacted') ? 'selected' : '' ?>>Contacted</option>
                    <option value="Qualified" <?= (isset($_GET['status']) && $_GET['status'] == 'Qualified') ? 'selected' : '' ?>>Qualified</option>
                    <option value="Proposal Sent" <?= (isset($_GET['status']) && $_GET['status'] == 'Proposal Sent') ? 'selected' : '' ?>>Proposal Sent</option>
                    <option value="Won" <?= (isset($_GET['status']) && $_GET['status'] == 'Won') ? 'selected' : '' ?>>Won</option>
                    <option value="Lost" <?= (isset($_GET['status']) && $_GET['status'] == 'Lost') ? 'selected' : '' ?>>Lost</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="High" <?= (isset($_GET['priority']) && $_GET['priority'] == 'High') ? 'selected' : '' ?>>High</option>
                    <option value="Medium" <?= (isset($_GET['priority']) && $_GET['priority'] == 'Medium') ? 'selected' : '' ?>>Medium</option>
                    <option value="Low" <?= (isset($_GET['priority']) && $_GET['priority'] == 'Low') ? 'selected' : '' ?>>Low</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-2"></i>Filter</button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><a href="?sort=name&dir=<?= $orderDir == 'ASC' ? 'desc' : 'asc' ?>" class="text-decoration-none text-dark">Name <i class="bi bi-arrow-down-up small ms-1"></i></a></th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <p class="fs-5 mb-0">No leads found.</p>
                            <p class="small">Try adjusting your search or filters.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($leads as $lead): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($lead['name']) ?></div>
                                <div class="small-text text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($lead['company'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <div class="small-text"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['email'] ?? 'N/A') ?></div>
                                <div class="small-text"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($lead['phone'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'bg-secondary';
                                if($lead['status'] == 'New') $badgeClass = 'status-new';
                                if($lead['status'] == 'Contacted') $badgeClass = 'status-contacted';
                                if($lead['status'] == 'Qualified') $badgeClass = 'status-qualified';
                                if($lead['status'] == 'Proposal Sent') $badgeClass = 'status-proposal';
                                if($lead['status'] == 'Won') $badgeClass = 'status-won';
                                if($lead['status'] == 'Lost') $badgeClass = 'status-lost';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $lead['status'] ?></span>
                            </td>
                            <td>
                                <?php
                                $pClass = 'bg-secondary';
                                if($lead['priority'] == 'High') $pClass = 'priority-high';
                                if($lead['priority'] == 'Medium') $pClass = 'priority-medium';
                                if($lead['priority'] == 'Low') $pClass = 'priority-low';
                                ?>
                                <span class="badge <?= $pClass ?>"><?= $lead['priority'] ?></span>
                            </td>
                            <td>
                                <span class="small-text"><?= date('M d, Y', strtotime($lead['created_at'])) ?></span>
                            </td>
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
    <div class="card-footer bg-white border-0 py-3">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>" tabindex="-1">Previous</a>
                </li>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
