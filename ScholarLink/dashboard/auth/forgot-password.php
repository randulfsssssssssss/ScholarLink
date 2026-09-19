<?php require_once SCHOLARLINK_ROOT . '/includes/header.php'; ?>

<div class="app-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Reset Your Password</h1>
                <p>Enter your email to receive a reset link</p>
            </div>

            <form id="forgot-form" class="auth-form" method="POST" action="/api/v1/auth/forgot-password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((new Csrf(SessionManager::getInstance()))->getToken()) ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>

            <div class="auth-footer">
                <p><a href="/login">Back to Sign In</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once SCHOLARLINK_ROOT . '/includes/footer.php'; ?>
