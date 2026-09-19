<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Saved Scholarships</h1>
        <p>Scholarships you have bookmarked for later review.</p>
    </div>
</div>

<div class="container">
    <div id="bookmarks-list" class="scholarship-grid"></div>
    <div id="bookmarks-empty" class="empty-state" style="display:none;">
        <p>You haven't bookmarked any scholarships yet.</p>
        <a href="/scholarships" class="btn btn-primary">Browse Scholarships</a>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initBookmarksPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('My Bookmarks', $content, $authUser);
?>
