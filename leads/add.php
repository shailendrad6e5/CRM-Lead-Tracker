<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

$pageTitle = 'Add Lead';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $source = $_POST['source'] ?? '';
    $status = $_POST['status'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $assigned_to = $_SESSION['user_id'];

    // Enum validation
    $valid_statuses = ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'];
    $valid_priorities = ['Low', 'Medium', 'High'];
    $valid_sources = ['Website', 'Referral', 'Cold Call', 'Email Campaign', 'Other'];

    if (!in_array($status, $valid_statuses)) $status = 'New';
    if (!in_array($priority, $valid_priorities)) $priority = 'Medium';
    if (!in_array($source, $valid_sources)) $source = 'Other';

    if (empty($name)) {
        $_SESSION['error'] = "Name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO leads (name, company, email, phone, source, status, priority, assigned_to, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if($stmt->execute([$name, $company, $email, $phone, $source, $status, $priority, $assigned_to, $notes])) {
            $_SESSION['success'] = "Lead added successfully.";
            header("Location: " . BASE_URL . "/leads/list.php");
            exit;
        } else {
            $_SESSION['error'] = "Failed to add lead.";
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Add New Lead</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/leads/list.php" class="text-decoration-none">Leads</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add Lead</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <h5 class="mb-4 text-primary border-bottom pb-2"><i class="bi bi-person-lines-fill me-2"></i>Contact Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Please enter the lead's name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="company" class="form-label">Company Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                <input type="text" class="form-control" id="company" name="company">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email">
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-4 text-primary border-bottom pb-2 mt-5"><i class="bi bi-funnel me-2"></i>Lead Details</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="New">New</option>
                                <option value="Contacted">Contacted</option>
                                <option value="Qualified">Qualified</option>
                                <option value="Proposal Sent">Proposal Sent</option>
                                <option value="Won">Won</option>
                                <option value="Lost">Lost</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="source" class="form-label">Lead Source</label>
                            <select class="form-select" id="source" name="source">
                                <option value="Website">Website</option>
                                <option value="Referral">Referral</option>
                                <option value="Cold Call">Cold Call</option>
                                <option value="Email Campaign">Email Campaign</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="500" placeholder="Add any additional details here..."></textarea>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>/leads/list.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Save Lead</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4 d-none d-lg-block">
        <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <img src="<?= BASE_URL ?>/assets/images/add_illustration.svg" alt="" class="img-fluid mb-4 px-4" onerror="this.style.display='none'">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-plus text-primary display-4"></i>
                </div>
                <h4>Adding a New Lead</h4>
                <p class="text-muted small px-3">Make sure to fill out as much information as possible to help your sales team close the deal efficiently.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
