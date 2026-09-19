<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>My Scholarships</h1>
        <p>Manage all scholarship listings you have created.</p>
    </div>
</div>

<div class="container">
    <div class="section-header">
        <h2>Scholarship Listings</h2>
        <a href="/scholarships/create" class="btn btn-primary">Create New</a>
    </div>
    <div id="org-scholarships-list" class="table-container"></div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initOrgScholarshipList();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('My Scholarships', $content, $authUser);
?>
