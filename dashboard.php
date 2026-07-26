<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle = 'Dashboard';

// Get statistics
$stats = [
    'total' => 0,
    'new' => 0,
    'contacted' => 0,
    'qualified' => 0,
    'proposal' => 0,
    'won' => 0,
    'lost' => 0
];

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM leads WHERE assigned_to = ? GROUP BY status");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $stats['total'] += $row['count'];
    if ($row['status'] == 'New') $stats['new'] = $row['count'];
    elseif ($row['status'] == 'Contacted') $stats['contacted'] = $row['count'];
    elseif ($row['status'] == 'Qualified') $stats['qualified'] = $row['count'];
    elseif ($row['status'] == 'Proposal Sent') $stats['proposal'] = $row['count'];
    elseif ($row['status'] == 'Won') $stats['won'] = $row['count'];
    elseif ($row['status'] == 'Lost') $stats['lost'] = $row['count'];
}

// Get recent leads
$recentStmt = $pdo->prepare("SELECT * FROM leads WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 5");
$recentStmt->execute([$user_id]);
$recentLeads = $recentStmt->fetchAll();

include 'includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php" class="text-decoration-none">
            <div class="card h-100 border-start border-primary border-4 text-center p-3">
                <h2 class="display-6 fw-bold text-primary mb-1"><?= $stats['total'] ?></h2>
                <p class="text-muted mb-0 fw-medium">Total Leads</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php?status=New" class="text-decoration-none">
            <div class="card h-100 border-start border-info border-4 text-center p-3">
                <h2 class="display-6 fw-bold text-info mb-1"><?= $stats['new'] ?></h2>
                <p class="text-muted mb-0 fw-medium">New</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php?status=Won" class="text-decoration-none">
            <div class="card h-100 border-start border-success border-4 text-center p-3">
                <h2 class="display-6 fw-bold text-success mb-1"><?= $stats['won'] ?></h2>
                <p class="text-muted mb-0 fw-medium">Won</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php?status=Lost" class="text-decoration-none">
            <div class="card h-100 border-start border-secondary border-4 text-center p-3">
                <h2 class="display-6 fw-bold text-secondary mb-1"><?= $stats['lost'] ?></h2>
                <p class="text-muted mb-0 fw-medium">Lost</p>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fs-5 fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Leads</h4>
                <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recentLeads)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <div class="mb-2"><i class="bi bi-inbox fs-2"></i></div>
                                    No leads found.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($recentLeads as $lead): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none d-block"><?= htmlspecialchars($lead['name']) ?></a>
                                        <div class="small-text text-muted"><?= htmlspecialchars($lead['company']) ?></div>
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
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-primary" title="View"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4 p-0">
            <div class="card-header">
                <h4 class="mb-0 fs-5 fw-semibold"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h4>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="<?= BASE_URL ?>/leads/add.php" class="btn btn-primary d-flex align-items-center justify-content-center py-2">
                        <i class="bi bi-plus-circle me-2"></i> Add New Lead
                    </a>
                    <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center py-2">
                        <i class="bi bi-search me-2"></i> Search Leads
                    </a>
                </div>
            </div>
        </div>

        <div class="card p-0">
            <div class="card-header">
                <h4 class="mb-0 fs-5 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Pipeline</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Contacted</span>
                    <span class="fw-semibold"><?= $stats['contacted'] ?></span>
                </div>
                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar status-contacted" role="progressbar" style="width: <?= $stats['total'] > 0 ? ($stats['contacted']/$stats['total']*100) : 0 ?>%"></div>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Qualified</span>
                    <span class="fw-semibold"><?= $stats['qualified'] ?></span>
                </div>
                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar status-qualified" role="progressbar" style="width: <?= $stats['total'] > 0 ? ($stats['qualified']/$stats['total']*100) : 0 ?>%"></div>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Proposal Sent</span>
                    <span class="fw-semibold"><?= $stats['proposal'] ?></span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar status-proposal" role="progressbar" style="width: <?= $stats['total'] > 0 ? ($stats['proposal']/$stats['total']*100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
