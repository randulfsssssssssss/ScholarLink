<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Application Management</h1>
        <p>View and manage all applications across the platform.</p>
    </div>
</div>

<div class="container">
    <div class="filters-bar">
        <select id="status-filter">
            <option value="">All Statuses</option>
            <option value="started">Started</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="declined">Declined</option>
            <option value="withdrawn">Withdrawn</option>
        </select>
    </div>

    <div class="table-container">
        <table class="data-table" id="admin-applications-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Scholarship</th>
                    <th>Organization</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-applications-tbody">
                <tr><td colspan="8" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initAdminApplicationsPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Application Management', $content, $authUser);
?>
