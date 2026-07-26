<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();

$pageTitle = 'Edit Lead';

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ? AND assigned_to = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$lead = $stmt->fetch();

if (!$lead) {
    $_SESSION['error'] = "Lead not found.";
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name           = trim($_POST['name'] ?? '');
    $company        = trim($_POST['company'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $source         = $_POST['source'] ?? '';
    $status         = $_POST['status'] ?? '';
    $priority       = $_POST['priority'] ?? '';
    $notes          = trim($_POST['notes'] ?? '');
    $followup_date  = !empty($_POST['followup_date'])  ? $_POST['followup_date']  : null;
    $followup_notes = trim($_POST['followup_notes'] ?? '');

    // Enum validation
    $valid_statuses  = ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'];
    $valid_priorities= ['Low', 'Medium', 'High'];
    $valid_sources   = ['Website', 'Referral', 'Cold Call', 'Email Campaign', 'Other'];

    if (!in_array($status,   $valid_statuses))  $status   = $lead['status'];
    if (!in_array($priority, $valid_priorities)) $priority = $lead['priority'];
    if (!in_array($source,   $valid_sources))   $source   = $lead['source'];

    if (empty($name)) {
        $_SESSION['error'] = "Name is required.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("UPDATE leads SET name=?, company=?, email=?, phone=?, source=?, status=?, priority=?, notes=?, followup_date=?, followup_notes=? WHERE id=? AND assigned_to=?");
        if ($stmt->execute([$name, $company, $email, $phone, $source, $status, $priority, $notes, $followup_date, $followup_notes, $id, $_SESSION['user_id']])) {
            // Build activity description
            $changes = [];
            if ($lead['status']   !== $status)   $changes[] = "Status changed from {$lead['status']} to {$status}";
            if ($lead['priority'] !== $priority)  $changes[] = "Priority changed from {$lead['priority']} to {$priority}";
            if ($lead['name']     !== $name)      $changes[] = "Name updated";
            $desc = !empty($changes) ? implode('; ', $changes) : 'Lead details updated';
            logLeadActivity($pdo, $id, $_SESSION['user_id'], 'edited', $desc);

            $_SESSION['success'] = "Lead updated successfully.";
            header("Location: " . BASE_URL . "/leads/view.php?id=" . $id);
            exit;
        } else {
            $_SESSION['error'] = "Failed to update lead.";
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Edit Lead: <?= htmlspecialchars($lead['name']) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/leads/list.php" class="text-decoration-none">Leads</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($lead['name']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <?= csrfField() ?>
                    <h5 class="mb-4 text-primary border-bottom pb-2"><i class="bi bi-person-lines-fill me-2"></i>Contact Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($lead['name']) ?>" required>
                                <div class="invalid-feedback">Please enter the lead's name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="company" class="form-label">Company Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                <input type="text" class="form-control" id="company" name="company" value="<?= htmlspecialchars($lead['company'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($lead['email'] ?? '') ?>">
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($lead['phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-4 text-primary border-bottom pb-2 mt-5"><i class="bi bi-funnel me-2"></i>Lead Details</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php
                                $statuses = ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'];
                                foreach ($statuses as $st) {
                                    $selected = $lead['status'] === $st ? 'selected' : '';
                                    echo "<option value=\"$st\" $selected>$st</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <?php
                                $priorities = ['Low', 'Medium', 'High'];
                                foreach ($priorities as $pr) {
                                    $selected = $lead['priority'] === $pr ? 'selected' : '';
                                    echo "<option value=\"$pr\" $selected>$pr</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="source" class="form-label">Lead Source</label>
                            <select class="form-select" id="source" name="source">
                                <?php
                                $sources = ['Website', 'Referral', 'Cold Call', 'Email Campaign', 'Other'];
                                foreach ($sources as $src) {
                                    $selected = $lead['source'] === $src ? 'selected' : '';
                                    echo "<option value=\"$src\" $selected>$src</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500"><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
                    </div>

                    <h5 class="mb-4 text-primary border-bottom pb-2 mt-4"><i class="bi bi-calendar-check me-2"></i>Follow-up</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="followup_date" class="form-label">Follow-up Date</label>
                            <input type="date" class="form-control" id="followup_date" name="followup_date" value="<?= htmlspecialchars($lead['followup_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="followup_notes" class="form-label">Follow-up Notes</label>
                            <input type="text" class="form-control" id="followup_notes" name="followup_notes" value="<?= htmlspecialchars($lead['followup_notes'] ?? '') ?>" placeholder="What to discuss?">
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Update Lead</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
