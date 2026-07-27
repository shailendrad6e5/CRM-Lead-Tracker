<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
requireRole(['admin']);

$pageTitle = 'Create Team Member';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name       = trim($_POST['name']      ?? '');
    $email      = trim($_POST['email']     ?? '');
    $password   = $_POST['password']       ?? '';
    $role       = $_POST['role']           ?? 'sales_rep';
    $department = trim($_POST['department'] ?? '');
    $phone      = trim($_POST['phone']     ?? '');
    $job_title  = trim($_POST['job_title'] ?? '');
    $status     = $_POST['status']         ?? 'active';

    $validRoles    = ['admin','manager','sales_rep'];
    $validStatuses = ['active','inactive','suspended'];
    if (!in_array($role,   $validRoles, true))    $role   = 'sales_rep';
    if (!in_array($status, $validStatuses, true)) $status = 'active';

    if (empty($name))                     $errors[] = 'Full name is required.';
    if (empty($email))                    $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($password))                 $errors[] = 'Password is required.';
    elseif (strlen($password) < 8)        $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        // Check duplicate email
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Email is already registered.';
        }
    }

    $avatar = null;
    if (empty($errors)) {
        $upload = storeAvatarUpload($_FILES['avatar'] ?? [], 'avatar');
        if ($upload['error']) {
            $errors[] = $upload['error'];
        } else {
            $avatar = $upload['filename'];
        }
    }

    if (empty($errors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, department, phone, job_title, avatar, status, created_by, requires_password_change) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
            $stmt->execute([$name, $email, $hashed, $role, $department, $phone, $job_title, $avatar, $status, $_SESSION['user_id']]);
            $newUserId = $pdo->lastInsertId();
            logUserActivity($pdo, $_SESSION['user_id'], 'User Created', "Created new user: {$email} ({$role})");
            sendNotification($pdo, $newUserId, 'account_created', 'Welcome to the Team!',
                "Your account has been created. Role: " . getRoleLabel($role), BASE_URL . '/profile.php');
            $_SESSION['success'] = "Team member '{$name}' created successfully.";
            header('Location: ' . BASE_URL . '/team.php');
            exit;
        } catch (Throwable $e) {
            if ($avatar) {
                deleteAvatarFile($avatar);
            }
            error_log('Team member creation failed: ' . $e->getMessage());
            $errors[] = 'Failed to create user.';
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add Team Member</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/team.php" class="text-decoration-none">Team</a></li>
                <li class="breadcrumb-item active">Create Member</li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/team.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrfField() ?>

                    <h6 class="fw-semibold text-primary border-bottom pb-2 mb-3"><i class="bi bi-person me-2"></i>Personal Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Job Title</label>
                            <input type="text" class="form-control" name="job_title" value="<?= htmlspecialchars($_POST['job_title'] ?? '') ?>" placeholder="e.g. Sales Executive">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Avatar (optional)</label>
                        <input type="file" class="form-control" name="avatar" accept="image/*">
                        <div class="form-text">JPG, PNG, GIF or WEBP — Max 2MB</div>
                    </div>

                    <h6 class="fw-semibold text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-shield me-2"></i>Role & Access</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role">
                                <option value="sales_rep" <?= ($_POST['role']??'sales_rep')==='sales_rep'?'selected':'' ?>>Sales Rep</option>
                                <option value="manager"   <?= ($_POST['role']??'')==='manager'?'selected':'' ?>>Manager</option>
                                <?php if (hasRole('admin')): ?>
                                <option value="admin"     <?= ($_POST['role']??'')==='admin'?'selected':'' ?>>Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>" placeholder="e.g. Sales, Marketing">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active"   <?= ($_POST['status']??'active')==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= ($_POST['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                                <option value="suspended" <?= ($_POST['status']??'')==='suspended'?'selected':'' ?>>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-semibold text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-lock me-2"></i>Password</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="newUserPass" required minlength="8">
                                <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generatePassword()">
                                <i class="bi bi-shuffle me-1"></i>Generate
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>/team.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-person-plus me-2"></i>Create Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Role Permissions</h6>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <span class="badge role-admin mb-1">Admin</span>
                        <ul class="small text-muted ps-3 mb-0">
                            <li>Full system access</li>
                            <li>Manage users & team</li>
                            <li>View all leads & reports</li>
                            <li>Assign any lead</li>
                        </ul>
                    </div>
                    <div>
                        <span class="badge role-manager mb-1">Manager</span>
                        <ul class="small text-muted ps-3 mb-0">
                            <li>View & edit all leads</li>
                            <li>Assign leads to reps</li>
                            <li>View team reports</li>
                            <li>Cannot manage users</li>
                        </ul>
                    </div>
                    <div>
                        <span class="badge role-sales-rep mb-1">Sales Rep</span>
                        <ul class="small text-muted ps-3 mb-0">
                            <li>Only their assigned leads</li>
                            <li>Cannot assign to others</li>
                            <li>Cannot access team page</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generatePassword() {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
    let pass = '';
    for (let i = 0; i < 12; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('newUserPass').value = pass;
    document.getElementById('newUserPass').type = 'text';
    setTimeout(() => document.getElementById('newUserPass').type = 'password', 3000);
}
</script>

<?php include '../includes/footer.php'; ?>
