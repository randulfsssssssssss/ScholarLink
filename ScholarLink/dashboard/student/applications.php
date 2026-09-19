<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>My Applications</h1>
        <p>Track the status of your scholarship applications.</p>
    </div>
</div>

<div class="container">
    <div class="table-container">
        <table class="data-table" id="applications-table">
            <thead>
                <tr>
                    <th>Scholarship</th>
                    <th>Organization</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="applications-tbody">
                <tr><td colspan="6" class="loading">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="application-detail-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div id="application-detail-body"></div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initApplicationsPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('My Applications', $content, $authUser);
?>
