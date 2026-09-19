<?php require_once SCHOLARLINK_ROOT . '/includes/header.php'; ?>

<div class="app-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Set New Password</h1>
                <p>Enter your new password below</p>
            </div>

            <?php if (isset($_GET['token'])): ?>
            <form id="reset-form" class="auth-form" method="POST" action="/api/v1/auth/reset-password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((new Csrf(SessionManager::getInstance()))->getToken()) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                <div class="form-group">
                    <label for="password">New Password (min 8 characters)</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
            <?php else: ?>
            <div class="alert alert-error">Invalid or missing reset token.</div>
            <p><a href="/forgot" class="btn btn-outline">Request a Reset Link</a></p>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="/login">Back to Sign In</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once SCHOLARLINK_ROOT . '/includes/footer.php'; ?>
