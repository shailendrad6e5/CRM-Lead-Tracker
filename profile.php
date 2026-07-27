<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'My Profile';
$user_id   = $_SESSION['user_id'];

// Fetch user data (all columns now)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    // ── Update Profile ────────────────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name       = trim($_POST['name']       ?? '');
        $phone      = trim($_POST['phone']      ?? '');
        $job_title  = trim($_POST['job_title']  ?? '');

        if (empty($name)) {
            $_SESSION['error'] = "Name is required.";
        } else {
            $avatar = $user['avatar'];
            // Handle avatar upload
            if (!empty($_FILES['avatar']['name'])) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                    $dest = __DIR__ . '/assets/avatars/' . $filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                        if (!empty($user['avatar']) && file_exists(__DIR__ . '/assets/avatars/' . $user['avatar'])) {
                            unlink(__DIR__ . '/assets/avatars/' . $user['avatar']);
                        }
                        $avatar = $filename;
                    }
                }
            }

            $upStmt = $pdo->prepare("UPDATE users SET name=?, phone=?, job_title=?, avatar=? WHERE id=?");
            if ($upStmt->execute([$name, $phone, $job_title, $avatar, $user_id])) {
                $_SESSION['user_name']       = $name;
                $_SESSION['user_avatar']     = $avatar;
                $_SESSION['user_job_title']  = $job_title;
                $_SESSION['success'] = "Profile updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update profile.";
            }
        }
        header("Location: " . BASE_URL . "/profile.php");
        exit;
    }

    // ── Change Password ───────────────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = "All password fields are required.";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error'] = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
        } elseif (!password_verify($current_password, $user['password']) && $current_password !== $user['password']) {
            $_SESSION['error'] = "Current password is incorrect.";
        } else {
            $hashed   = password_hash($new_password, PASSWORD_DEFAULT);
            $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($passStmt->execute([$hashed, $user_id])) {
                logUserActivity($pdo, $user_id, 'Password Changed', 'User changed their own password from profile.');
                $_SESSION['success'] = "Password changed successfully.";
            } else {
                $_SESSION['error'] = "Failed to change password.";
            }
        }
        header("Location: " . BASE_URL . "/profile.php");
        exit;
    }
}

// Re-fetch after possible update
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// ── Lead Stats ────────────────────────────────────────────────────────────────
$lStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='Won') as won, SUM(status='Lost') as lost, SUM(status NOT IN ('Won','Lost')) as active FROM leads WHERE assigned_to=?");
$lStmt->execute([$user_id]);
$leadStats = $lStmt->fetch();

// ── Recent Activity ───────────────────────────────────────────────────────────
$actStmt = $pdo->prepare("SELECT la.*, l.name as lead_name FROM lead_activities la LEFT JOIN leads l ON la.lead_id = l.id WHERE la.user_id = ? ORDER BY la.created_at DESC LIMIT 10");
$actStmt->execute([$user_id]);
$recentActivity = $actStmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">My Profile</h1>
        <p class="text-muted small mb-0">Manage your account settings and preferences</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Avatar & Stats -->
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card text-center pt-5 pb-4 mb-3">
            <div class="card-body">
                <div class="position-relative d-inline-block mb-3">
                    <?= getUserAvatarHtml($user, 'xl') ?>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <?php if (!empty($user['job_title'])): ?>
                <p class="text-muted small mb-2"><?= htmlspecialchars($user['job_title']) ?></p>
                <?php endif; ?>
                <span class="badge <?= getRoleBadgeClass($user['role'] ?? 'sales_rep') ?> mb-2"><?= getRoleLabel($user['role'] ?? 'sales_rep') ?></span>
                <?php if (!empty($user['department'])): ?>
                <span class="badge bg-light text-dark border mb-2"><?= htmlspecialchars($user['department']) ?></span>
                <?php endif; ?>
                <hr>
                <div class="text-start small px-2">
                    <?php if (!empty($user['phone'])): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted"><i class="bi bi-telephone me-1"></i>Phone</span>
                        <span><?= htmlspecialchars($user['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted"><i class="bi bi-calendar me-1"></i>Member Since</span>
                        <span><?= date('M Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted"><i class="bi bi-clock me-1"></i>Last Login</span>
                        <span><?= !empty($user['last_login']) ? timeAgo($user['last_login']) : 'Now' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Stats -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2 text-primary"></i>My Lead Stats</h6></div>
            <div class="card-body p-0">
                <div class="row g-0 text-center">
                    <div class="col-3 py-3 border-end">
                        <div class="fw-bold fs-4"><?= $leadStats['total'] ?? 0 ?></div>
                        <div class="small text-muted">Total</div>
                    </div>
                    <div class="col-3 py-3 border-end">
                        <div class="fw-bold fs-4 text-primary"><?= $leadStats['active'] ?? 0 ?></div>
                        <div class="small text-muted">Active</div>
                    </div>
                    <div class="col-3 py-3 border-end">
                        <div class="fw-bold fs-4 text-success"><?= $leadStats['won'] ?? 0 ?></div>
                        <div class="small text-muted">Won</div>
                    </div>
                    <div class="col-3 py-3">
                        <div class="fw-bold fs-4 text-danger"><?= $leadStats['lost'] ?? 0 ?></div>
                        <div class="small text-muted">Lost</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</h6></div>
            <div class="card-body p-0">
                <?php if (empty($recentActivity)): ?>
                <p class="text-muted small text-center py-3">No activity yet.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentActivity as $act): ?>
                    <li class="list-group-item px-3 py-2">
                        <div class="small fw-medium"><?= htmlspecialchars($act['description'] ?: $act['action']) ?></div>
                        <div class="small text-muted"><?= timeAgo($act['created_at']) ?> · <?= htmlspecialchars($act['lead_name'] ?? '—') ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Edit Forms -->
    <div class="col-lg-8">
        <!-- Personal Information -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2 text-primary"></i>Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrfField() ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <small class="text-muted">(Contact admin to change)</small></label>
                            <input type="email" class="form-control bg-light" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. +91 98765 43210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Job Title</label>
                            <input type="text" class="form-control" name="job_title" value="<?= htmlspecialchars($user['job_title'] ?? '') ?>" placeholder="e.g. Sales Executive">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Department <small class="text-muted">(Contact admin to change)</small></label>
                            <input type="text" class="form-control bg-light" name="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="avatar" accept="image/*">
                            <div class="form-text">JPG, PNG, WEBP — Max 2MB</div>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control" name="current_password" required>
                            <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                                <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-check-circle"></i></span>
                                <input type="password" class="form-control" name="confirm_password" required>
                                <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="change_password">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-secondary"><i class="bi bi-key me-2"></i>Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
