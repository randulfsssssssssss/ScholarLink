<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Application Review</h1>
        <p>Review applications submitted to your scholarships.</p>
    </div>
</div>

<div class="container">
    <div class="table-container">
        <table class="data-table" id="org-applications-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Scholarship</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="org-applications-tbody">
                <tr><td colspan="6" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="review-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div id="review-modal-body"></div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initOrgApplicationsPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Application Review', $content, $authUser);
?>
