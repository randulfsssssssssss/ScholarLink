<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>User Management</h1>
        <p>Manage all platform users.</p>
    </div>
</div>

<div class="container">
    <div class="table-container">
        <table class="data-table" id="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <tr><td colspan="8" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initAdminUsersPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('User Management', $content, $authUser);
?>
