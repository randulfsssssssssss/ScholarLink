<?php
/** @var array $authUser */
$scholarshipId = (int)($_GET['id'] ?? $_GET['scholarship_id'] ?? 0);
if (!$scholarshipId) {
    $pathParts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $scholarshipId = (int)($pathParts[1] ?? 0);
}
?>
<div class="dashboard-header">
    <div class="container">
        <button class="btn btn-link" onclick="history.back()">&larr; Back</button>
        <div id="scholarship-detail" class="scholarship-detail"></div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderPage('Scholarship Details', $content, $authUser);
?>
