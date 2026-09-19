<?php
/** @var array $authUser */
$scholarshipId = (int)($_GET['id'] ?? 0);
?>
<div class="dashboard-header">
    <div class="container">
        <button class="btn btn-link" onclick="history.back()">&larr; Back to Dashboard</button>
        <h1>Create New Scholarship</h1>
    </div>
</div>

<div class="container">
    <form id="create-scholarship-form" class="form-card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((new Csrf(SessionManager::getInstance()))->getToken()) ?>">

        <div class="form-group">
            <label for="title">Scholarship Title *</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="6" required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="amount">Award Amount ($)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0" value="0" required>
            </div>
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="deadline">Application Deadline *</label>
                <input type="date" id="deadline" name="deadline" required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Requirements</label>
            <div id="requirements-list">
                <div class="requirement-item">
                    <select name="requirements[0][requirement_type]">
                        <option value="transcript">Official Transcript</option>
                        <option value="recommendation">Letter of Recommendation</option>
                        <option value="essay">Personal Essay</option>
                        <option value="portfolio">Portfolio / Work Samples</option>
                        <option value="financial_info">Financial Information</option>
                        <option value="identification">Identification Document</option>
                        <option value="other">Other Document</option>
                    </select>
                    <input type="text" name="requirements[0][label]" placeholder="Document name" required>
                    <textarea name="requirements[0][description]" placeholder="Description"></textarea>
                    <label class="checkbox-label">
                        <input type="checkbox" name="requirements[0][is_required]" checked> Required
                    </label>
                    <button type="button" class="btn btn-sm btn-outline remove-req">Remove</button>
                </div>
            </div>
            <button type="button" id="add-requirement" class="btn btn-outline btn-sm">Add Requirement</button>
        </div>

        <button type="submit" class="btn btn-primary">Save Scholarship</button>
    </form>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initScholarshipForm();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Create Scholarship', $content, $authUser);
?>
