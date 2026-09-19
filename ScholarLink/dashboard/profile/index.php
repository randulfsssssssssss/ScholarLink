<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Profile</h1>
        <p>Manage your personal information.</p>
    </div>
</div>

<div class="container">
    <div class="profile-grid">
        <div class="profile-card">
            <h2>Personal Information</h2>
            <form id="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" readonly>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <div id="student-profile-fields" class="form-row conditional-fields">
                    <div class="form-group">
                        <label for="school">School / University</label>
                        <input type="text" id="school" name="school">
                    </div>
                    <div class="form-group">
                        <label for="graduation_year">Graduation Year</label>
                        <input type="number" id="graduation_year" name="graduation_year" min="1900" max="2100">
                    </div>
                </div>

                <div id="org-profile-fields" class="form-row conditional-fields" style="display:none;">
                    <div class="form-group">
                        <label for="organization_name">Organization Name</label>
                        <input type="text" id="organization_name" name="organization_name">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

        <div class="profile-card">
            <h2>Security</h2>
            <form id="change-password-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>

            <div class="profile-completion">
                <h3>Profile Completion</h3>
                <div class="progress-bar">
                    <div class="progress-fill" id="completion-bar" style="width: 0%"></div>
                </div>
                <span id="completion-text">0%</span>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initProfilePage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Profile', $content, $authUser);
?>
