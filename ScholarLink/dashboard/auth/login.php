<?php require_once SCHOLARLINK_ROOT . '/includes/header.php'; ?>

<div class="app-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Sign In to ScholarLink</h1>
                <p>Access your scholarship dashboard</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $errors = [
                    'invalid' => 'Invalid email or password.',
                    'login_required' => 'Please log in to continue.',
                    'forbidden' => 'You do not have permission to access that page.',
                ];
                echo $errors[$_GET['error']] ?? 'An error occurred.';
                ?>
            </div>
            <?php endif; ?>

            <form id="login-form" class="auth-form" method="POST" action="/api/v1/auth/login">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((new Csrf(SessionManager::getInstance()))->getToken()) ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" minlength="8">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="auth-footer">
                <p><a href="/forgot">Forgot your password?</a></p>
                <p>Don't have an account? <a href="/register">Register</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once SCHOLARLINK_ROOT . '/includes/footer.php'; ?>
