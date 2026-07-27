<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// We explicitly check for login here, bypassing requireLogin() which would redirect us back here
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ?, requires_password_change = 0 WHERE id = ?');
        
        if ($stmt->execute([$hashed, $_SESSION['user_id']])) {
            require_once 'includes/helpers.php';
            logUserActivity($pdo, $_SESSION['user_id'], 'Password Changed', 'User changed their password via forced update.');
            
            $_SESSION['requires_password_change'] = 0;
            $_SESSION['success'] = 'Password updated successfully.';
            
            $redirect = in_array($_SESSION['user_role'] ?? 'sales_rep', ['admin', 'manager']) ? '/dashboard.php' : '/my_leads.php';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - CRM Lead Tracker</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .brand-logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #184E77, #1A759F);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }
    </style>
</head>
<body>

    <div class="card border-0 login-card p-4">
        <div class="text-center mb-4">
            <div class="brand-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h4 class="fw-bold mb-1">Update Password</h4>
            <p class="text-muted small">For security, you must update your temporary password before continuing.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 text-center small rounded-3"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="needs-validation" novalidate>
            <?= getCsrfField() ?>
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                    <div class="invalid-feedback">Please enter your new password.</div>
                </div>
                <div class="form-text small">Must be at least 6 characters.</div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Save Password <i class="bi bi-check-circle ms-2"></i></button>
        </form>
        
        <div class="mt-4 text-center small">
            <a href="logout.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Return to Login</a>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
