<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Scholarship Management</h1>
        <p>Manage all scholarships on the platform.</p>
    </div>
</div>

<div class="container">
    <div class="table-container">
        <table class="data-table" id="admin-scholarships-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-scholarships-tbody">
                <tr><td colspan="9" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initAdminScholarshipsPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Scholarship Management', $content, $authUser);
?>
