<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

$id = (int)$_GET['id'];

// ── Quick Status Change ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    verifyCsrfToken();
    $newStatus  = $_POST['quick_status'];
    $valid = ['New','Contacted','Qualified','Proposal Sent','Won','Lost'];
    if (in_array($newStatus, $valid)) {
        // Admin/manager can change status on any lead
        if (hasAnyRole(['admin','manager'])) {
            $oldRow = $pdo->prepare("SELECT status FROM leads WHERE id=?");
            $oldRow->execute([$id]);
            $oldStatus = $oldRow->fetchColumn();
            $updateStmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $updateStmt->execute([$newStatus, $id]);
        } else {
            $oldRow = $pdo->prepare("SELECT status FROM leads WHERE id=? AND assigned_to=?");
            $oldRow->execute([$id, $_SESSION['user_id']]);
            $oldStatus = $oldRow->fetchColumn();
            $updateStmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ? AND assigned_to = ?");
            $updateStmt->execute([$newStatus, $id, $_SESSION['user_id']]);
        }
        logLeadActivity($pdo, $id, $_SESSION['user_id'], 'status_changed', "Status changed from {$oldStatus} to {$newStatus}");
        $_SESSION['success'] = "Lead marked as $newStatus.";
    }
    header("Location: " . BASE_URL . "/leads/view.php?id=" . $id);
    exit;
}

// ── Follow-up Quick Complete ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_followup'])) {
    verifyCsrfToken();
    $updateStmt = $pdo->prepare("UPDATE leads SET followup_status = 'Completed', completed_at = NOW() WHERE id = ? AND assigned_to = ?");
    $updateStmt->execute([$id, $_SESSION['user_id']]);
    logLeadActivity($pdo, $id, $_SESSION['user_id'], 'status_changed', "Follow-up marked as Completed");
    $_SESSION['success'] = "Follow-up completed.";
    header("Location: " . BASE_URL . "/leads/view.php?id=" . $id);
    exit;
}

// ── Add Note ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    verifyCsrfToken();
    $note = trim($_POST['note_text'] ?? '');
    if (!empty($note)) {
        $nStmt = $pdo->prepare("INSERT INTO lead_notes (lead_id, user_id, note) VALUES (?, ?, ?)");
        $nStmt->execute([$id, $_SESSION['user_id'], $note]);
        logLeadActivity($pdo, $id, $_SESSION['user_id'], 'note_added', 'Note added');
        $_SESSION['success'] = "Note added.";
    }
    header("Location: " . BASE_URL . "/leads/view.php?id=" . $id);
    exit;
}

// ── Fetch Lead ────────────────────────────────────────────────────────────
// Admin/manager can view any lead; sales_rep only their own
if (hasAnyRole(['admin','manager'])) {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name as assigned_name, u.role as assigned_role, u.avatar as assigned_avatar, u.job_title as assigned_job_title
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        WHERE l.id = ?
    ");
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name as assigned_name, u.role as assigned_role, u.avatar as assigned_avatar, u.job_title as assigned_job_title
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        WHERE l.id = ? AND l.assigned_to = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
}
$lead = $stmt->fetch();

if (!$lead) {
    $_SESSION['error'] = "Lead not found or access denied.";
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

// ── Fetch Notes ───────────────────────────────────────────────────────────
$notes = [];
try {
    $nStmt = $pdo->prepare("SELECT ln.*, u.name as author FROM lead_notes ln JOIN users u ON ln.user_id=u.id WHERE ln.lead_id=? ORDER BY ln.created_at DESC");
    $nStmt->execute([$id]);
    $notes = $nStmt->fetchAll() ?: [];
} catch (Exception $e) {}

// ── Fetch Timeline ────────────────────────────────────────────────────────
$activities = [];
try {
    $aStmt = $pdo->prepare("SELECT la.*, u.name as actor FROM lead_activities la JOIN users u ON la.user_id=u.id WHERE la.lead_id=? ORDER BY la.created_at DESC LIMIT 20");
    $aStmt->execute([$id]);
    $activities = $aStmt->fetchAll() ?: [];
} catch (Exception $e) {}

$pageTitle = htmlspecialchars($lead['name']);
include '../includes/header.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0">Lead Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/leads/list.php" class="text-decoration-none">Leads</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($lead['name']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-2"></i>Back</a>
        <?php if($lead['status'] !== 'Won'): ?>
        <form method="POST" class="d-inline"><?= csrfField() ?>
            <input type="hidden" name="quick_status" value="Won">
            <button class="btn btn-sm btn-success"><i class="bi bi-check-circle me-1"></i>Mark Won</button>
        </form>
        <?php endif; ?>
        <?php if($lead['status'] !== 'Lost'): ?>
        <form method="POST" class="d-inline"><?= csrfField() ?>
            <input type="hidden" name="quick_status" value="Lost">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Mark Lost</button>
        </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/leads/edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $lead['id'] ?>" data-url="<?= BASE_URL ?>/leads/list.php">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- ── LEFT COLUMN ─────────────────────────────────────────────── -->
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center pt-5">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;">
                    <i class="bi bi-person fs-1"></i>
                </div>
                <h3 class="h4 mb-1 fw-bold"><?= htmlspecialchars($lead['name']) ?></h3>
                <p class="text-muted mb-3"><i class="bi bi-building me-2"></i><?= htmlspecialchars(!empty($lead['company']) ? $lead['company'] : 'No Company') ?></p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge <?= getStatusBadgeClass($lead['status']) ?> px-3 py-2"><?= $lead['status'] ?></span>
                    <span class="badge <?= getPriorityBadgeClass($lead['priority']) ?> px-3 py-2"><?= $lead['priority'] ?> Priority</span>
                </div>
                <hr>
                <div class="text-start">
                    <label class="text-muted small fw-semibold text-uppercase d-block mb-2">Contact</label>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope text-primary me-3 fs-5"></i>
                        <?php if($lead['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="text-dark fw-medium text-decoration-none small"><?= htmlspecialchars($lead['email']) ?></a>
                        <?php else: ?><span class="text-muted small">Not provided</span><?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-telephone text-primary me-3 fs-5"></i>
                        <?php if($lead['phone']): ?>
                            <a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="text-dark fw-medium text-decoration-none small"><?= htmlspecialchars($lead['phone']) ?></a>
                        <?php else: ?><span class="text-muted small">Not provided</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Follow-up Card -->
        <?php if (!empty($lead['followup_date'])): ?>
        <?php
            $fStatus = $lead['followup_status'] ?? 'Pending';
            $fState  = computeFollowUpState($lead['followup_date'], $lead['followup_time'], $fStatus);
            $fClass  = getFollowUpStateBadgeClass($fState);
            $fIcon   = ($fState === 'Overdue') ? 'bi-exclamation-circle text-danger' : (($fState === 'Today') ? 'bi-alarm text-warning' : 'bi-calendar-check text-primary');
            if ($fState === 'Completed') $fIcon = 'bi-check-circle text-success';
        ?>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0"><i class="bi <?= $fIcon ?> me-2"></i>Follow-up</h6>
                    <span class="badge <?= $fClass ?>"><?= $fState ?></span>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Scheduled For</label>
                    <p class="mb-0 fw-medium">
                        <?= date('M j, Y', strtotime($lead['followup_date'])) ?>
                        <?= !empty($lead['followup_time']) ? ' at ' . date('h:i A', strtotime($lead['followup_time'])) : '' ?>
                    </p>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Priority</label>
                        <span class="badge <?= getPriorityBadgeClass($lead['followup_priority'] ?? 'Medium') ?>"><?= $lead['followup_priority'] ?? 'Medium' ?></span>
                    </div>
                    <?php if ($fStatus === 'Completed' && !empty($lead['completed_at'])): ?>
                    <div class="text-end">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Completed On</label>
                        <span class="small fw-medium"><?= date('M j, Y h:i A', strtotime($lead['completed_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if(!empty($lead['followup_notes'])): ?>
                <div class="bg-light p-2 rounded mb-3">
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($lead['followup_notes']) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($fStatus !== 'Completed'): ?>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="complete_followup" value="1">
                    <button type="submit" class="btn btn-success w-100 btn-sm fw-medium"><i class="bi bi-check2-all me-1"></i>Mark as Completed</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lead Meta Card -->
        <div class="card shadow-sm">
            <div class="card-body">
                <label class="text-muted small fw-semibold text-uppercase d-block mb-2">Lead Info</label>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-muted">Source</span>
                    <span class="small fw-medium"><i class="bi <?= getSourceIcon($lead['source'] ?? '') ?> me-1"></i><?= htmlspecialchars($lead['source'] ?? 'Unknown') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-muted">Assigned To</span>
                    <span class="small fw-medium"><?= htmlspecialchars($lead['assigned_name'] ?? 'Unassigned') ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="small text-muted">Created</span>
                    <span class="small fw-medium"><?= date('M j, Y', strtotime($lead['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="small text-muted">Updated</span>
                    <span class="small fw-medium"><?= timeAgo($lead['updated_at']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT COLUMN ────────────────────────────────────────────── -->
    <div class="col-lg-8">
        <!-- Lead Notes (Main) -->
        <?php if(!empty($lead['notes'])): ?>
        <div class="card shadow-sm mb-4 p-0">
            <div class="card-header"><h5 class="mb-0 fw-semibold fs-6"><i class="bi bi-journal-text me-2 text-primary"></i>Notes & Context</h5></div>
            <div class="card-body">
                <div class="bg-light p-3 rounded border">
                    <p class="mb-0" style="white-space:pre-wrap;"><?= htmlspecialchars($lead['notes']) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Multiple Notes Section -->
        <div class="card shadow-sm mb-4 p-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold fs-6"><i class="bi bi-chat-left-text me-2 text-primary"></i>Notes
                    <?php if(!empty($notes)): ?><span class="badge bg-primary ms-1"><?= count($notes) ?></span><?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <!-- Add Note Form -->
                <form method="POST" class="mb-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="add_note" value="1">
                    <div class="d-flex gap-2">
                        <textarea class="form-control" name="note_text" rows="2" placeholder="Add a note..." required style="resize:none;"></textarea>
                        <button type="submit" class="btn btn-primary px-3"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </form>

                <!-- Notes List -->
                <?php if(empty($notes)): ?>
                <div class="text-center py-4 text-muted bg-light rounded">
                    <i class="bi bi-chat-left fs-2 d-block mb-2"></i>
                    <p class="small mb-0">No notes yet. Add the first one above.</p>
                </div>
                <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach($notes as $note): ?>
                    <div class="d-flex gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;font-size:14px;">
                            <?= strtoupper(substr($note['author'], 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="bg-light p-3 rounded border">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-semibold small"><?= htmlspecialchars($note['author']) ?></span>
                                    <span class="text-muted" style="font-size:11px;"><?= timeAgo($note['created_at']) ?></span>
                                </div>
                                <p class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($note['note']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card shadow-sm p-0">
            <div class="card-header">
                <h5 class="mb-0 fw-semibold fs-6"><i class="bi bi-clock-history me-2 text-primary"></i>Activity Timeline</h5>
            </div>
            <div class="card-body">
                <?php if(empty($activities)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-hourglass fs-2 d-block mb-2"></i>
                    <p class="small mb-0">No activity logged yet.</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach($activities as $act): ?>
                    <?php
                        $iconMap = [
                            'created'        => 'bi-plus-circle-fill text-success',
                            'edited'         => 'bi-pencil-fill text-warning',
                            'status_changed' => 'bi-arrow-repeat text-info',
                            'note_added'     => 'bi-chat-fill text-primary'
                        ];
                        $icon = $iconMap[$act['action']] ?? 'bi-circle-fill text-secondary';
                    ?>
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0 mt-1">
                            <i class="bi <?= $icon ?>" style="font-size:1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0 small fw-medium"><?= htmlspecialchars($act['description']) ?></p>
                            <p class="mb-0 text-muted" style="font-size:11px;">by <?= htmlspecialchars($act['actor']) ?> · <?= timeAgo($act['created_at']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
