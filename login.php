<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Handle remember me if needed
            
            header('Location: ' . BASE_URL . '/dashboard.php');
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
    <title><?= $pageTitle ?> - <?= SITE_TITLE ?></title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-wrapper">
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
                    <span class="input-group-text bg-light cursor-pointer"><i class="bi bi-eye-slash toggle-password"></i></span>
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
        
        <div class="mt-4 text-center small text-muted">
            Don't have an account? <a href="register.php" class="text-primary text-decoration-none fw-medium">Sign up here</a>
        </div>
        
        <div class="mt-4 p-3 bg-light rounded border text-center small text-muted cursor-pointer" onclick="document.getElementById('email').value='admin@example.com'; document.getElementById('password').value='admin123';" style="transition: background-color 0.2s;" onmouseover="this.classList.remove('bg-light'); this.classList.add('bg-white', 'shadow-sm');" onmouseout="this.classList.add('bg-light'); this.classList.remove('bg-white', 'shadow-sm');">
            <p class="mb-1 fw-semibold"><i class="bi bi-info-circle me-1"></i> Demo Credentials (Click to auto-fill)</p>
            <div>Email: <span class="fw-medium text-dark">admin@example.com</span></div>
            <div>Password: <span class="fw-medium text-dark">admin123</span></div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
