<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle  = 'Dashboard';
$loadCharts = true; // Tell footer.php to include Chart.js

$user_id = $_SESSION['user_id'];

// ── Lead status stats ──────────────────────────────────────────────────────
$stats = ['total'=>0,'new'=>0,'contacted'=>0,'qualified'=>0,'proposal'=>0,'won'=>0,'lost'=>0];
$stmt  = $pdo->prepare("SELECT status, COUNT(*) as count FROM leads WHERE assigned_to = ? GROUP BY status");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $stats['total'] += $row['count'];
    $key = strtolower(str_replace(' ', '_', $row['status']));
    // map 'proposal_sent' -> 'proposal'
    if ($key === 'proposal_sent') $key = 'proposal';
    if (isset($stats[$key])) $stats[$key] = $row['count'];
}

// Win rate
$winRate = $stats['total'] > 0 ? round(($stats['won'] / $stats['total']) * 100, 1) : 0;

// ── Monthly leads for bar chart (last 6 months) ────────────────────────────
$monthlyStmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month_label,
           DATE_FORMAT(created_at, '%Y-%m') as month_key,
           COUNT(*) as count
    FROM leads
    WHERE assigned_to = ?
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key ASC
");
$monthlyStmt->execute([$user_id]);
$monthlyData   = $monthlyStmt->fetchAll();
$monthLabels   = array_column($monthlyData, 'month_label');
$monthCounts   = array_column($monthlyData, 'count');

// ── Today's follow-ups ─────────────────────────────────────────────────────
$todayFollowups = [];
try {
    $fStmt = $pdo->prepare("SELECT * FROM leads WHERE assigned_to = ? AND followup_date = CURDATE() AND followup_status != 'Completed' ORDER BY name");
    $fStmt->execute([$user_id]);
    $todayFollowups = $fStmt->fetchAll();
} catch (Exception $e) { /* table may not exist yet */ }

// ── Overdue follow-ups ─────────────────────────────────────────────────────
$overdueFollowups = [];
try {
    $oStmt = $pdo->prepare("SELECT * FROM leads WHERE assigned_to = ? AND followup_date < CURDATE() AND followup_status != 'Completed' AND status NOT IN ('Won','Lost') ORDER BY followup_date ASC LIMIT 5");
    $oStmt->execute([$user_id]);
    $overdueFollowups = $oStmt->fetchAll();
} catch (Exception $e) { /* table may not exist yet */ }

// ── Recent leads ───────────────────────────────────────────────────────────
$recentStmt = $pdo->prepare("SELECT * FROM leads WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 6");
$recentStmt->execute([$user_id]);
$recentLeads = $recentStmt->fetchAll();

include 'includes/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- STAT CARDS                                                              -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php" class="text-decoration-none">
            <div class="card h-100 p-3 text-center border-start border-primary border-4">
                <h2 class="display-6 fw-bold text-primary mb-1"><?= $stats['total'] ?></h2>
                <p class="text-muted mb-0 small fw-medium">Total Leads</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php?status=New" class="text-decoration-none">
            <div class="card h-100 p-3 text-center border-start border-info border-4">
                <h2 class="display-6 fw-bold text-info mb-1"><?= $stats['new'] ?></h2>
                <p class="text-muted mb-0 small fw-medium">New</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php?status=Won" class="text-decoration-none">
            <div class="card h-100 p-3 text-center border-start border-success border-4">
                <h2 class="display-6 fw-bold text-success mb-1"><?= $stats['won'] ?></h2>
                <p class="text-muted mb-0 small fw-medium">Won <span class="text-muted fw-normal">(<?= $winRate ?>%)</span></p>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/leads/list.php?status=Lost" class="text-decoration-none">
            <div class="card h-100 p-3 text-center border-start border-secondary border-4">
                <h2 class="display-6 fw-bold text-secondary mb-1"><?= $stats['lost'] ?></h2>
                <p class="text-muted mb-0 small fw-medium">Lost</p>
            </div>
        </a>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- CHARTS ROW                                                              -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <!-- Doughnut Chart: Status Distribution -->
    <div class="col-lg-4">
        <div class="card p-0 h-100">
            <div class="card-header">
                <h5 class="mb-0 fs-6 fw-semibold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Lead Status Distribution</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if ($stats['total'] > 0): ?>
                <canvas id="statusChart" style="max-height: 260px;"></canvas>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                    <p class="small">Add leads to see chart</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bar Chart: Monthly Leads -->
    <div class="col-lg-8">
        <div class="card p-0 h-100">
            <div class="card-header">
                <h5 class="mb-0 fs-6 fw-semibold"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Monthly Leads (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($monthlyData)): ?>
                <canvas id="monthlyChart" style="max-height: 260px;"></canvas>
                <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bar-chart fs-1 d-block mb-2"></i>
                    <p class="small">No data yet for monthly chart</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- MAIN CONTENT ROW                                                        -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="row g-4">
    <!-- Recent Leads Table -->
    <div class="col-lg-8">
        <div class="card p-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fs-6 fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Leads</h5>
                <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell">Priority</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recentLeads)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                    <p class="text-muted mb-2">No leads yet</p>
                                    <a href="<?= BASE_URL ?>/leads/add.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Add First Lead</a>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($recentLeads as $lead): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none d-block"><?= htmlspecialchars($lead['name']) ?></a>
                                        <div class="small text-muted"><?= htmlspecialchars($lead['company'] ?? '') ?></div>
                                    </td>
                                    <td><span class="badge <?= getStatusBadgeClass($lead['status']) ?>"><?= $lead['status'] ?></span></td>
                                    <td class="d-none d-md-table-cell"><span class="badge <?= getPriorityBadgeClass($lead['priority']) ?>"><?= $lead['priority'] ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-primary"><i class="bi bi-eye"></i></a>
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

    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 fs-6 fw-semibold"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/leads/add.php" class="btn btn-primary d-flex align-items-center justify-content-center">
                        <i class="bi bi-plus-circle me-2"></i>Add New Lead
                    </a>
                    <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center">
                        <i class="bi bi-people me-2"></i>All Leads
                    </a>
                    <a href="<?= BASE_URL ?>/leads/list.php?priority=High" class="btn btn-outline-danger d-flex align-items-center justify-content-center">
                        <i class="bi bi-fire me-2"></i>High Priority
                    </a>
                </div>
            </div>
        </div>

        <!-- Pipeline Progress -->
        <div class="card mb-4 p-0">
            <div class="card-header">
                <h5 class="mb-0 fs-6 fw-semibold"><i class="bi bi-funnel me-2"></i>Pipeline</h5>
            </div>
            <div class="card-body">
                <?php
                $pipeline = [
                    'Contacted'     => ['count' => $stats['contacted'],  'class' => 'status-contacted'],
                    'Qualified'     => ['count' => $stats['qualified'],  'class' => 'status-qualified'],
                    'Proposal Sent' => ['count' => $stats['proposal'],   'class' => 'status-proposal'],
                ];
                foreach ($pipeline as $label => $data):
                    $pct = $stats['total'] > 0 ? ($data['count'] / $stats['total'] * 100) : 0;
                ?>
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-muted"><?= $label ?></span>
                    <span class="fw-semibold small"><?= $data['count'] ?></span>
                </div>
                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar <?= $data['class'] ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Today's Follow-ups -->
        <?php if (!empty($todayFollowups) || !empty($overdueFollowups)): ?>
        <div class="card p-0 mb-4">
            <div class="card-header">
                <h5 class="mb-0 fs-6 fw-semibold"><i class="bi bi-calendar-check me-2 text-warning"></i>Follow-ups</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($todayFollowups)): ?>
                <div class="px-3 pt-3 pb-1">
                    <p class="text-muted small fw-semibold text-uppercase mb-2">Today</p>
                    <?php foreach ($todayFollowups as $fl): ?>
                    <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-warning bg-opacity-10 rounded border border-warning border-opacity-25">
                        <i class="bi bi-alarm text-warning"></i>
                        <div class="flex-grow-1 overflow-hidden">
                            <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $fl['id'] ?>" class="text-dark text-decoration-none fw-medium small d-block text-truncate"><?= htmlspecialchars($fl['name']) ?></a>
                            <div class="text-muted d-flex justify-content-between align-items-center" style="font-size:11px;">
                                <span class="text-truncate me-2"><?= htmlspecialchars($fl['followup_notes'] ?? 'Follow-up today') ?></span>
                                <?= !empty($fl['followup_time']) ? '<span class="fw-semibold text-nowrap">'.date('h:i A', strtotime($fl['followup_time'])).'</span>' : '' ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($overdueFollowups)): ?>
                <div class="px-3 pt-2 pb-3">
                    <p class="text-muted small fw-semibold text-uppercase mb-2 mt-1">Overdue</p>
                    <?php foreach ($overdueFollowups as $ov): ?>
                    <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25">
                        <i class="bi bi-exclamation-circle text-danger"></i>
                        <div class="flex-grow-1 overflow-hidden">
                            <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $ov['id'] ?>" class="text-dark text-decoration-none fw-medium small d-block text-truncate"><?= htmlspecialchars($ov['name']) ?></a>
                            <div class="text-danger d-flex justify-content-between align-items-center" style="font-size:11px;">
                                <span><?= date('M d', strtotime($ov['followup_date'])) ?></span>
                                <span class="badge <?= getPriorityBadgeClass($ov['followup_priority'] ?? 'Medium') ?>" style="font-size:9px; padding:3px 5px;"><?= $ov['followup_priority'] ?? 'Medium' ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="card-footer bg-white border-top text-center p-2">
                    <a href="<?= BASE_URL ?>/followups.php" class="btn btn-sm btn-link text-decoration-none">View All Follow-ups</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- CHART.JS INITIALIZATION                                                 -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<?php if ($stats['total'] > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Color palette matching style.css
    const colors = {
        new:       '#168AAD',
        contacted: '#1E6091',
        qualified: '#52B69A',
        proposal:  '#34A0A4',
        won:       '#99D98C',
        lost:      '#6c757d'
    };

    // ── Doughnut: Status distribution ──────────────────────────────────
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'],
                datasets: [{
                    data: [
                        <?= $stats['new'] ?>,
                        <?= $stats['contacted'] ?>,
                        <?= $stats['qualified'] ?>,
                        <?= $stats['proposal'] ?>,
                        <?= $stats['won'] ?>,
                        <?= $stats['lost'] ?>
                    ],
                    backgroundColor: Object.values(colors),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Inter', size: 11 }, padding: 12, boxWidth: 12 }
                    }
                }
            }
        });
    }

    // ── Bar: Monthly leads ──────────────────────────────────────────────
    <?php if (!empty($monthlyData)): ?>
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [{
                    label: 'Leads Added',
                    data: <?= json_encode($monthCounts) ?>,
                    backgroundColor: 'rgba(22, 138, 173, 0.25)',
                    borderColor: '#168AAD',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
