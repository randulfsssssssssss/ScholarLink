<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Organization Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($authUser['organization_name'] ?: ($authUser['first_name'] . ' ' . $authUser['last_name'])) ?>!</p>
    </div>
</div>

<div class="container">
    <div class="dashboard-grid">
        <div class="card stat-card">
            <div class="stat-value" id="stat-scholarships">0</div>
            <div class="stat-label">My Scholarships</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-applications-reviewed">0</div>
            <div class="stat-label">Applications Received</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-applications-pending">0</div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-total-awards">0</div>
            <div class="stat-label">Total Award Value</div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>My Scholarships</h2>
            <a href="/scholarships/create" class="btn btn-primary">Create New Scholarship</a>
        </div>
        <div id="org-scholarships-list" class="table-container"></div>
    </div>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Recent Applications</h2>
            <a href="/applications" class="link-secondary">View all &rarr;</a>
        </div>
        <div id="org-applications-list" class="table-container"></div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initOrganizationDashboard();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Organization Dashboard', $content, $authUser);
?>
