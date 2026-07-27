<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Follow-ups';
$user_id = $_SESSION['user_id'];

// Quick Complete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_followup'])) {
    verifyCsrfToken();
    $id = (int)$_POST['lead_id'];
    $stmt = $pdo->prepare("UPDATE leads SET followup_status = 'Completed', completed_at = NOW() WHERE id = ? AND assigned_to = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->rowCount() > 0) {
        logLeadActivity($pdo, $id, $user_id, 'followup_completed', "Follow-up marked as Completed from Follow-ups Hub");
        $_SESSION['success'] = "Follow-up completed.";
    } else {
        $_SESSION['error'] = 'Follow-up not found or already completed.';
    }
    header("Location: " . BASE_URL . "/followups.php");
    exit;
}

// Fetch Follow-ups
$where = "assigned_to = ? AND COALESCE(followup_status, 'Pending') != 'Completed' AND followup_date IS NOT NULL";
$params = [$user_id];

$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $where .= " AND (name LIKE ? OR company LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$fPriority = $_GET['priority'] ?? '';
if (!empty($fPriority)) {
    $where .= " AND followup_priority = ?";
    $params[] = $fPriority;
}

$stmt = $pdo->prepare("SELECT id, name, company, followup_date, followup_time, followup_priority, followup_notes, followup_status FROM leads WHERE $where ORDER BY followup_date ASC, followup_time ASC");
$stmt->execute($params);
$leads = $stmt->fetchAll();

$todayDate = date('Y-m-d');
$overdue = [];
$today = [];
$upcoming = [];

foreach ($leads as $l) {
    if ($l['followup_date'] < $todayDate) {
        $overdue[] = $l;
    } elseif ($l['followup_date'] === $todayDate) {
        $today[] = $l;
    } else {
        $upcoming[] = $l;
    }
}

// Helper to render card
function renderFollowUpCard($lead, $isOverdue = false) {
    $icon = $isOverdue ? 'bi-exclamation-circle text-danger' : 'bi-calendar-check text-primary';
    if ($lead['followup_date'] === date('Y-m-d')) $icon = 'bi-alarm text-warning';
    
    $pClass = getPriorityBadgeClass($lead['followup_priority'] ?? 'Medium');
    $timeStr = !empty($lead['followup_time']) ? date('h:i A', strtotime($lead['followup_time'])) : '';
    $dateStr = date('M d, Y', strtotime($lead['followup_date']));
    
    $csrf = csrfField();
    $baseUrl = BASE_URL;
    $id = $lead['id'];
    $name = htmlspecialchars($lead['name']);
    $company = htmlspecialchars($lead['company'] ?? '');
    $notes = htmlspecialchars($lead['followup_notes'] ?? '');
    $priority = $lead['followup_priority'] ?? 'Medium';

    $notesHtml = !empty($notes) ? "<p class='small text-muted mb-3 p-2 bg-light rounded'><i class='bi bi-info-circle me-1'></i>{$notes}</p>" : "<div class='mb-3'></div>";

    return <<<HTML
<div class="card shadow-sm mb-3 border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <a href="{$baseUrl}/leads/view.php?id={$id}" class="fw-semibold text-dark text-decoration-none fs-6 d-block">{$name}</a>
                <span class="small text-muted"><i class="bi bi-building me-1"></i>{$company}</span>
            </div>
            <span class="badge {$pClass}">{$priority}</span>
        </div>
        <div class="d-flex align-items-center mb-2 small text-muted">
            <i class="bi {$icon} me-2"></i>
            <span class="fw-medium">{$dateStr} {$timeStr}</span>
        </div>
        {$notesHtml}
        <form method="POST" action="" class="mb-0 text-end">
            {$csrf}
            <input type="hidden" name="complete_followup" value="1">
            <input type="hidden" name="lead_id" value="{$id}">
            <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check2-all me-1"></i>Complete</button>
        </form>
    </div>
</div>
HTML;
}

include 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Follow-ups Hub</h1>
        <p class="text-muted small mb-0">Manage all your scheduled sales calls, meetings, and emails</p>
    </div>
    <form action="" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="text" name="search" class="form-control form-control-sm mb-0" style="width: auto;" placeholder="Search leads..." value="<?= htmlspecialchars($search) ?>">
        <select name="priority" class="form-select form-select-sm mb-0" style="width: auto; min-width: 140px;">
            <option value="">All Priorities</option>
            <option value="High" <?= $fPriority==='High'?'selected':'' ?>>High</option>
            <option value="Medium" <?= $fPriority==='Medium'?'selected':'' ?>>Medium</option>
            <option value="Low" <?= $fPriority==='Low'?'selected':'' ?>>Low</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary mb-0">Filter</button>
        <a href="followups.php" class="btn btn-sm btn-outline-secondary mb-0">Clear</a>
    </form>
</div>

<div class="row g-4">
    <!-- Overdue Column -->
    <div class="col-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-danger">
            <h5 class="mb-0 fw-semibold text-danger"><i class="bi bi-exclamation-circle me-2"></i>Overdue</h5>
            <span class="badge bg-danger rounded-pill"><?= count($overdue) ?></span>
        </div>
        <div class="kanban-column" style="min-height: 400px; background: rgba(220,53,69,0.03); padding: 10px; border-radius: 8px;">
            <?php
            if (empty($overdue)) {
                echo '<div class="text-center py-5 text-muted small"><i class="bi bi-emoji-smile fs-3 d-block mb-2 text-success"></i>No overdue follow-ups!</div>';
            } else {
                foreach ($overdue as $l) {
                    echo renderFollowUpCard($l, true);
                }
            }
            ?>
        </div>
    </div>

    <!-- Today Column -->
    <div class="col-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-warning">
            <h5 class="mb-0 fw-semibold text-warning" style="color: #d96404 !important;"><i class="bi bi-alarm me-2"></i>Today</h5>
            <span class="badge bg-warning text-dark rounded-pill"><?= count($today) ?></span>
        </div>
        <div class="kanban-column" style="min-height: 400px; background: rgba(253,126,20,0.03); padding: 10px; border-radius: 8px;">
            <?php
            if (empty($today)) {
                echo '<div class="text-center py-5 text-muted small"><i class="bi bi-cup-hot fs-3 d-block mb-2"></i>Nothing scheduled for today.</div>';
            } else {
                foreach ($today as $l) {
                    echo renderFollowUpCard($l, false);
                }
            }
            ?>
        </div>
    </div>

    <!-- Upcoming Column -->
    <div class="col-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-primary">
            <h5 class="mb-0 fw-semibold text-primary"><i class="bi bi-calendar-event me-2"></i>Upcoming</h5>
            <span class="badge bg-primary rounded-pill"><?= count($upcoming) ?></span>
        </div>
        <div class="kanban-column" style="min-height: 400px; background: rgba(13,110,253,0.03); padding: 10px; border-radius: 8px;">
            <?php
            if (empty($upcoming)) {
                echo '<div class="text-center py-5 text-muted small"><i class="bi bi-calendar fs-3 d-block mb-2"></i>No upcoming follow-ups.</div>';
            } else {
                foreach ($upcoming as $l) {
                    echo renderFollowUpCard($l, false);
                }
            }
            ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
