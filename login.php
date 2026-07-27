<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if (isLoggedIn() && refreshAuthenticatedUser()) {
    $redirect = hasAnyRole(['admin', 'manager']) ? '/dashboard.php' : '/my_leads.php';
    header('Location: ' . BASE_URL . $redirect);
    exit;
}

$error = '';
if (!empty($_SESSION['error'])) {
    $error = (string)$_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password, role, department, job_title, avatar, status, requires_password_change FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $valid = false;
        if ($user) {
            // Check account is active
            if (in_array($user['status'] ?? 'active', ['inactive', 'suspended'])) {
                $error = 'Your account has been disabled. Please contact your administrator.';
            } else {
                if (password_verify($password, $user['password'])) {
                    $valid = true;
                } elseif ($password === $user['password']) {
                    // Auto-migrate plain-text password to hashed on login
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);
                    $valid = true;
                }
            }
        }

        if ($valid) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id']         = $user['id'];
            $_SESSION['user_name']       = $user['name'];
            $_SESSION['user_role']       = $user['role'] ?? 'sales_rep';
            $_SESSION['user_department'] = $user['department'] ?? '';
            $_SESSION['user_job_title']  = $user['job_title'] ?? '';
            $_SESSION['user_avatar']     = $user['avatar'] ?? '';
            $_SESSION['requires_password_change'] = $user['requires_password_change'] ?? 0;
            // Update last login
            $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            logUserActivity($pdo, $user['id'], 'Login', 'User logged in via login form.');
            
            $redirect = in_array($user['role'] ?? 'sales_rep', ['admin', 'manager']) ? '/dashboard.php' : '/my_leads.php';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <title><?= $pageTitle ?> - <?= SITE_TITLE ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="<?= BASE_URL ?>/assets/js/theme.js?v=1.0"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=3.0">
</head>
<body class="auth-wrapper">
    <?php
    $appearanceClass = 'auth-appearance-controls';
    include __DIR__ . '/includes/theme_controls.php';
    unset($appearanceClass);
    ?>
    <div class="auth-card card">
        <div class="text-center mb-4">
            <h1 class="h3 fw-bold text-primary mb-2"><i class="bi bi-hexagon-fill me-2"></i>Mini CRM</h1>
            <p class="text-muted small">Sign in to manage your leads</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger p-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                    <label class="form-check-label small" for="rememberMe">Remember me</label>
                </div>
                <a href="#" class="small text-decoration-none text-primary">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In <i class="bi bi-box-arrow-in-right ms-2"></i></button>
        </form>
        

        
        <div class="mt-4">
            <p class="text-center small text-muted mb-2 fw-semibold"><i class="bi bi-info-circle me-1"></i> Demo Credentials (Click to auto-fill)</p>
            <div class="d-flex flex-column gap-2">
                <div class="p-2 border rounded text-center small cursor-pointer demo-btn" 
                     style="transition: all 0.2s; background: rgba(255,255,255,0.05);"
                     onclick="document.getElementById('email').value='admin@example.com'; document.getElementById('password').value='SecurePass2026!';" 
                     onmouseover="this.style.background='rgba(255,255,255,0.1)';" 
                     onmouseout="this.style.background='rgba(255,255,255,0.05)';">
                    <span class="fw-bold text-primary">Admin:</span> admin@example.com
                </div>
                <div class="p-2 border rounded text-center small cursor-pointer demo-btn" 
                     style="transition: all 0.2s; background: rgba(255,255,255,0.05);"
                     onclick="document.getElementById('email').value='manager@example.com'; document.getElementById('password').value='manager123';" 
                     onmouseover="this.style.background='rgba(255,255,255,0.1)';" 
                     onmouseout="this.style.background='rgba(255,255,255,0.05)';">
                    <span class="fw-bold text-success">Manager:</span> manager@example.com
                </div>
                <div class="p-2 border rounded text-center small cursor-pointer demo-btn" 
                     style="transition: all 0.2s; background: rgba(255,255,255,0.05);"
                     onclick="document.getElementById('email').value='saleperson1@example.com'; document.getElementById('password').value='sales@123';" 
                     onmouseover="this.style.background='rgba(255,255,255,0.1)';" 
                     onmouseout="this.style.background='rgba(255,255,255,0.05)';">
                    <span class="fw-bold text-warning">Sales:</span> saleperson1@example.com
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
