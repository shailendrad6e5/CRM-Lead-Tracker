<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/team.php');
    exit;
}

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([$id]);
$editUser = $userStmt->fetch();
if (!$editUser) {
    $_SESSION['error'] = 'User not found.';
    header('Location: ' . BASE_URL . '/team.php');
    exit;
}

$pageTitle = 'Edit — ' . $editUser['name'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name       = trim($_POST['name']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $role       = $_POST['role']            ?? $editUser['role'];
    $department = trim($_POST['department'] ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $job_title  = trim($_POST['job_title']  ?? '');
    $status     = $_POST['status']          ?? $editUser['status'];
    $new_pass   = $_POST['new_password']    ?? '';

    // Prevent self-demotion
    if ((int)$id === (int)$_SESSION['user_id'] && $role !== 'admin') {
        $errors[] = 'You cannot change your own role away from Admin.';
        $role = 'admin';
    }

    $validRoles    = ['admin','manager','sales_rep'];
    $validStatuses = ['active','inactive','suspended'];
    if (!in_array($role,   $validRoles, true))    $role   = $editUser['role'];
    if (!in_array($status, $validStatuses, true)) $status = $editUser['status'];

    if (empty($name))  $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    if (!empty($new_pass) && strlen($new_pass) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    // Check email uniqueness (excluding this user)
    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) $errors[] = 'That email is already in use.';
    }

    $avatar = $editUser['avatar'];
    $newAvatar = null;
    if (empty($errors)) {
        $upload = storeAvatarUpload($_FILES['avatar'] ?? [], 'avatar_' . $id);
        if ($upload['error']) {
            $errors[] = $upload['error'];
        } elseif ($upload['filename']) {
            $newAvatar = $upload['filename'];
            $avatar = $newAvatar;
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($new_pass)) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET name=?, email=?, role=?, department=?, phone=?, job_title=?, avatar=?, status=?, password=?, requires_password_change=1 WHERE id=?')
                    ->execute([$name, $email, $role, $department, $phone, $job_title, $avatar, $status, $hashed, $id]);
            } else {
                $pdo->prepare('UPDATE users SET name=?, email=?, role=?, department=?, phone=?, job_title=?, avatar=?, status=? WHERE id=?')
                    ->execute([$name, $email, $role, $department, $phone, $job_title, $avatar, $status, $id]);
            }

            if ($newAvatar && $editUser['avatar'] !== $newAvatar) {
                deleteAvatarFile($editUser['avatar']);
            }

            logUserActivity($pdo, $_SESSION['user_id'], 'User Updated', "Updated user: {$email}");
            $_SESSION['success'] = "User '{$name}' updated successfully.";
            header('Location: ' . BASE_URL . '/team.php');
            exit;
        } catch (Throwable $e) {
            if ($newAvatar) {
                deleteAvatarFile($newAvatar);
            }
            error_log('Team member update failed: ' . $e->getMessage());
            $errors[] = 'Failed to update user.';
        }
    }

    // Re-populate $editUser for display
    $editUser = array_merge($editUser, compact('name','email','role','department','phone','job_title','status'));
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Team Member</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/team.php" class="text-decoration-none">Team</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($editUser['name']) ?></li>
            </ol>
        </nav>
    </div>
    <a href="<?= BASE_URL ?>/team.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrfField() ?>

                    <h6 class="fw-semibold text-primary border-bottom pb-2 mb-3"><i class="bi bi-person me-2"></i>Personal Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($editUser['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Job Title</label>
                            <input type="text" class="form-control" name="job_title" value="<?= htmlspecialchars($editUser['job_title'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Update Avatar</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <?= getUserAvatarHtml($editUser, 'lg') ?>
                            <input type="file" class="form-control" name="avatar" accept="image/*" style="max-width:300px;">
                        </div>
                    </div>

                    <h6 class="fw-semibold text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-shield me-2"></i>Role & Access</h6>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" <?= (int)$id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="sales_rep" <?= $editUser['role']==='sales_rep'?'selected':'' ?>>Sales Rep</option>
                                <option value="manager"   <?= $editUser['role']==='manager'?'selected':'' ?>>Manager</option>
                                <?php if (hasRole('admin')): ?>
                                <option value="admin"     <?= $editUser['role']==='admin'?'selected':'' ?>>Admin</option>
                                <?php endif; ?>
                            </select>
                            <?php if ((int)$id === (int)$_SESSION['user_id']): ?>
                            <input type="hidden" name="role" value="admin">
                            <div class="form-text">Cannot change your own role.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($editUser['department'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" <?= (int)$id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="active"   <?= ($editUser['status']??'active')==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= ($editUser['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                                <option value="suspended" <?= ($editUser['status']??'')==='suspended'?'selected':'' ?>>Suspended</option>
                            </select>
                            <?php if ((int)$id === (int)$_SESSION['user_id']): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($editUser['status'] ?? 'active') ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <h6 class="fw-semibold text-primary border-bottom pb-2 mb-3 mt-2"><i class="bi bi-lock me-2"></i>Change Password <small class="text-muted fw-normal">(optional)</small></h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="new_password" id="editUserPass" minlength="8" placeholder="Leave blank to keep current">
                                <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>/team.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <?= getUserAvatarHtml($editUser, 'xl') ?>
                <h5 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($editUser['name']) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($editUser['email']) ?></p>
                <span class="badge <?= getRoleBadgeClass($editUser['role']) ?>"><?= getRoleLabel($editUser['role']) ?></span>
                <hr>
                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Last Login</span><span><?= !empty($editUser['last_login']) ? timeAgo($editUser['last_login']) : 'Never' ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Joined</span><span><?= date('M d, Y', strtotime($editUser['created_at'])) ?></span></div>
                </div>
            </div>
        </div>
        <?php
        // Leads assigned stats
        try {
            $lStats = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='Won') as won, SUM(status='Lost') as lost FROM leads WHERE assigned_to=?");
            $lStats->execute([$id]);
            $ls = $lStats->fetch();
        } catch (Exception $e) { $ls = null; }
        if ($ls): ?>
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart me-2 text-primary"></i>Lead Stats</h6>
                <div class="row g-2 text-center">
                    <div class="col-4"><div class="fw-bold fs-5"><?= $ls['total'] ?></div><div class="small text-muted">Assigned</div></div>
                    <div class="col-4"><div class="fw-bold fs-5 text-success"><?= $ls['won'] ?? 0 ?></div><div class="small text-muted">Won</div></div>
                    <div class="col-4"><div class="fw-bold fs-5 text-danger"><?= $ls['lost'] ?? 0 ?></div><div class="small text-muted">Lost</div></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
