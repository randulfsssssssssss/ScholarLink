<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Student Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($authUser['first_name']) ?>!</p>
    </div>
</div>

<div class="container">
    <div class="dashboard-grid">
        <div class="card stat-card">
            <div class="stat-value" id="stat-bookmarks">0</div>
            <div class="stat-label">Saved Scholarships</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-applications">0</div>
            <div class="stat-label">Active Applications</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="stat-deadlines">0</div>
            <div class="stat-label">Upcoming Deadlines</div>
        </div>
    </div>

    <div class="dashboard-section">
        <h2>Recommended Scholarships</h2>
        <p class="section-subtitle">New opportunities matching your interests</p>
        <div id="recommendations-list" class="scholarship-grid"></div>
    </div>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Your Applications</h2>
            <a href="/applications" class="link-secondary">View all &rarr;</a>
        </div>
        <div id="applications-list" class="table-container"></div>
    </div>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Saved Scholarships</h2>
            <a href="/bookmarks" class="link-secondary">View all &rarr;</a>
        </div>
        <div id="bookmarks-list" class="scholarship-grid-compact"></div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initStudentDashboard();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Student Dashboard', $content, $authUser);
?>
