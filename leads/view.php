<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    $newStatus = $_POST['quick_status'];
    $updateStmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ? AND assigned_to = ?");
    $updateStmt->execute([$newStatus, $id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Lead marked as $newStatus.";
    header("Location: " . BASE_URL . "/leads/view.php?id=" . $id);
    exit;
}

$stmt = $pdo->prepare("
    SELECT l.*, u.name as assigned_name 
    FROM leads l 
    LEFT JOIN users u ON l.assigned_to = u.id 
    WHERE l.id = ? AND l.assigned_to = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$lead = $stmt->fetch();

if (!$lead) {
    $_SESSION['error'] = "Lead not found.";
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

$pageTitle = htmlspecialchars($lead['name']);
include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Lead Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/leads/list.php" class="text-decoration-none">Leads</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($lead['name']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
        <?php if($lead['status'] !== 'Won'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="quick_status" value="Won">
            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-circle me-2"></i>Mark Won</button>
        </form>
        <?php endif; ?>
        <?php if($lead['status'] !== 'Lost'): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="quick_status" value="Lost">
            <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-x-circle me-2"></i>Mark Lost</button>
        </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/leads/edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $lead['id'] ?>" data-url="<?= BASE_URL ?>/leads/list.php">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Lead Profile Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center pt-5">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                    <i class="bi bi-person fs-1"></i>
                </div>
                <h3 class="h4 mb-1 fw-bold"><?= htmlspecialchars($lead['name']) ?></h3>
                <p class="text-muted mb-3"><i class="bi bi-building me-2"></i><?= htmlspecialchars($lead['company'] ?? 'No Company') ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <?php
                    $badgeClass = 'bg-secondary';
                    if($lead['status'] == 'New') $badgeClass = 'status-new';
                    if($lead['status'] == 'Contacted') $badgeClass = 'status-contacted';
                    if($lead['status'] == 'Qualified') $badgeClass = 'status-qualified';
                    if($lead['status'] == 'Proposal Sent') $badgeClass = 'status-proposal';
                    if($lead['status'] == 'Won') $badgeClass = 'status-won';
                    if($lead['status'] == 'Lost') $badgeClass = 'status-lost';
                    ?>
                    <span class="badge <?= $badgeClass ?> px-3 py-2 fs-6"><?= $lead['status'] ?></span>
                    
                    <?php
                    $pClass = 'bg-secondary';
                    if($lead['priority'] == 'High') $pClass = 'priority-high';
                    if($lead['priority'] == 'Medium') $pClass = 'priority-medium';
                    if($lead['priority'] == 'Low') $pClass = 'priority-low';
                    ?>
                    <span class="badge <?= $pClass ?> px-3 py-2 fs-6"><?= $lead['priority'] ?> Priority</span>
                </div>
                
                <hr>
                
                <div class="text-start">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Contact Info</label>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope text-primary me-3 fs-5"></i>
                            <div>
                                <?php if($lead['email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="text-decoration-none text-dark fw-medium"><?= htmlspecialchars($lead['email']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-telephone text-primary me-3 fs-5"></i>
                            <div>
                                <?php if($lead['phone']): ?>
                                    <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="text-decoration-none text-dark fw-medium"><?= htmlspecialchars($lead['phone']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Lead Information Card -->
        <div class="card shadow-sm mb-4 p-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Lead Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Source</label>
                        <p class="mb-0 fw-medium"><?= htmlspecialchars($lead['source'] ?? 'Unknown') ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Assigned To</label>
                        <p class="mb-0 fw-medium d-flex align-items-center">
                            <i class="bi bi-person-badge me-2 text-secondary"></i>
                            <?= htmlspecialchars($lead['assigned_name'] ?? 'Unassigned') ?>
                        </p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Created Date</label>
                        <p class="mb-0 fw-medium"><?= date('F j, Y, g:i a', strtotime($lead['created_at'])) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Last Updated</label>
                        <p class="mb-0 fw-medium"><?= date('F j, Y, g:i a', strtotime($lead['updated_at'])) ?></p>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div>
                    <label class="text-muted small fw-semibold text-uppercase d-block mb-2">Notes & Context</label>
                    <?php if(!empty($lead['notes'])): ?>
                        <div class="bg-light p-3 rounded border">
                            <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($lead['notes']) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 bg-light rounded border text-muted">
                            <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                            <p class="mb-0">No notes provided for this lead.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
