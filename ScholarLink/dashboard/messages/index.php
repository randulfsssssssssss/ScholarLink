<?php
/** @var array $authUser */
?>
<div class="dashboard-header">
    <div class="container">
        <h1>Messages</h1>
        <p>Communicate with students and organizations.</p>
    </div>
</div>

<div class="container">
    <div class="messages-layout">
        <div class="message-sidebar">
            <div class="message-sidebar-header">
                <button id="new-message-btn" class="btn btn-primary btn-sm">New Message</button>
                <div class="message-tabs">
                    <button class="tab-btn active" data-tab="inbox">Inbox</button>
                    <button class="tab-btn" data-tab="sent">Sent</button>
                </div>
            </div>
            <div id="message-list" class="message-list">
                <div class="loading">Loading messages...</div>
            </div>
        </div>
        <div id="message-detail" class="message-detail">
            <div class="message-placeholder">
                <p>Select a message to read</p>
            </div>
        </div>
    </div>
</div>

<div id="compose-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3>New Message</h3>
        <form id="compose-form">
            <div class="form-group">
                <label for="recipient_id">Recipient</label>
                <select id="recipient_id" name="recipient_id" required>
                    <option value="">Select recipient</option>
                </select>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="body">Message</label>
                <textarea id="body" name="body" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    if (window.ScholarLink) {
        window.ScholarLink.initMessagesPage();
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage('Messages', $content, $authUser);
?>
