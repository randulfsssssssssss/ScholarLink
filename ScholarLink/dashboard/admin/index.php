<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Platform administration panel</p>
    </div>
</div>

<div class="container">
    <div class="dashboard-grid">
        <div class="card stat-card">
            <div class="stat-value" id="stat-users">0</div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-students">0</div>
            <div class="stat-label">Students</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-organizations">0</div>
            <div class="stat-label">Organizations</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-scholarships">0</div>
            <div class="stat-label">Scholarships</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-applications">0</div>
            <div class="stat-label">Applications</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-audits">0</div>
            <div class="stat-label">Audit Logs</div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Recent Activity / Audit Log</h2>
        </div>
        <div id="audit-log-list" class="table-container"></div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initAdminDashboard();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Admin Dashboard', $content, $authUser);
?>
