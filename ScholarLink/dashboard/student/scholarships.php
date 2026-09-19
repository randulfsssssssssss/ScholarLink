<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>All Scholarships</h1>
        <p>Browse and apply for available scholarship opportunities.</p>
    </div>
</div>

<div class="container">
    <div class="filters-bar">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Search by title, description, organization...">
            <button id="search-btn" class="btn btn-outline">Search</button>
        </div>
        <div class="filter-group">
            <select id="category-filter">
                <option value="">All Categories</option>
            </select>
        </div>
    </div>

    <div id="scholarship-grid" class="scholarship-grid"></div>

    <div class="pagination" id="pagination"></div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initScholarshipBrowser();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Browse Scholarships', $content, $authUser);
?>
