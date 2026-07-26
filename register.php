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
    verifyCsrfToken();

    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered.';
        } else {
            // Store password securely with bcrypt
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            if ($insertStmt->execute([$name, $email, $hashed])) {
                $user_id = $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user_id;
                $_SESSION['user_name'] = $name;
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_TITLE ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
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
            <p class="text-muted small">Create an account to manage your leads</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger p-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    <div class="invalid-feedback">Please enter your name.</div>
                </div>
            </div>

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
                    <div class="invalid-feedback">Please enter a password.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-check-circle"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <span class="input-group-text bg-light cursor-pointer password-toggle-btn"><i class="bi bi-eye-slash"></i></span>
                    <div class="invalid-feedback">Please confirm your password.</div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign Up <i class="bi bi-person-plus ms-2"></i></button>
        </form>
        
        <div class="mt-4 text-center small text-muted">
            Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-medium">Sign in here</a>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
