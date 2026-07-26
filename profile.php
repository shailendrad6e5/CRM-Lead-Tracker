<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle = 'My Profile';
$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Update Profile Info
    if (isset($_POST['update_profile'])) {
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = "Name and Email are required.";
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            if ($updateStmt->execute([$name, $email, $user_id])) {
                $_SESSION['user_name'] = $name;
                $_SESSION['success'] = "Profile updated successfully.";
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $_SESSION['error'] = "Failed to update profile.";
            }
        }
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
        } elseif ($current_password !== $user['password']) {
            $_SESSION['error'] = "Current password is incorrect.";
        } else {
            $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($passStmt->execute([$new_password, $user_id])) {
                $_SESSION['success'] = "Password changed successfully.";
                // Update local user array to reflect new password
                $user['password'] = $new_password; 
            } else {
                $_SESSION['error'] = "Failed to change password.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
        <p class="text-muted small mb-0">Manage your account settings and preferences</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm text-center pt-5 pb-4">
            <div class="card-body">
                <div class="position-relative d-inline-block mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                        <span class="display-3"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                    </div>
                    <button class="btn btn-sm btn-light position-absolute bottom-0 end-0 rounded-circle shadow border" title="Change Photo">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge bg-success mb-3">Active Administrator</span>
                
                <hr>
                
                <div class="d-flex justify-content-between text-muted small px-2 mt-3">
                    <span>Member Since</span>
                    <span class="fw-medium text-dark"><?= date('M Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2 text-primary"></i>Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            <div class="invalid-feedback">Name is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            <div class="invalid-feedback">Valid email is required.</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="update_profile" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="invalid-feedback">Current password is required.</div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="invalid-feedback">New password is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Please confirm your new password.</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="change_password" class="btn btn-secondary"><i class="bi bi-key me-2"></i>Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
