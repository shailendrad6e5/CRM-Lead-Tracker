<?php
// CSRF Protection Helper

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token string.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the submitted CSRF token against the one in session.
 * Kills execution with 403 if invalid.
 */
function verifyCsrfToken(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (empty($submitted) || empty($stored) || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('403 Forbidden: Invalid CSRF token. Please go back and try again.');
    }
}

/**
 * Output a hidden CSRF input field inside a form.
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
