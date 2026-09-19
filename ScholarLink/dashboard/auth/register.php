<?php require_once SCHOLARLINK_ROOT . '/includes/header.php'; ?>

<div class="app-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Your Account</h1>
                <p>Join ScholarLink to find and apply for scholarships</p>
            </div>

            <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful. Please log in.</div>
            <?php endif; ?>

            <form id="register-form" class="auth-form" method="POST" action="/api/v1/auth/register">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((new Csrf(SessionManager::getInstance()))->getToken()) ?>">

                <div class="form-group">
                    <label>I am registering as</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="role" value="student" checked> Student
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="role" value="organization"> Organization
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password (min 8 characters)</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                </div>

                <div id="student-fields" class="conditional-fields">
                    <div class="form-group">
                        <label for="school">School / University</label>
                        <input type="text" id="school" name="school">
                    </div>
                    <div class="form-group">
                        <label for="graduation_year">Graduation Year</label>
                        <input type="number" id="graduation_year" name="graduation_year" min="1900" max="2100">
                    </div>
                </div>

                <div id="org-fields" class="conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="organization_name">Organization Name</label>
                        <input type="text" id="organization_name" name="organization_name">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="/login">Sign in</a></p>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="role"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var studentFields = document.getElementById('student-fields');
        var orgFields = document.getElementById('org-fields');
        if (this.value === 'organization') {
            studentFields.style.display = 'none';
            orgFields.style.display = 'block';
        } else {
            studentFields.style.display = 'block';
            orgFields.style.display = 'none';
        }
    });
});
</script>

<?php require_once SCHOLARLINK_ROOT . '/includes/footer.php'; ?>
