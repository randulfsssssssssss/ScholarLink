// ScholarLink - Organization Dashboard Module

(function () {
    'use strict';

    var SL = window.ScholarLink;

    SL.initOrganizationDashboard = function () {
        loadStats();
        loadScholarships();
        loadApplications();
    };

    function loadStats() {
        SL.get('applications?limit=1')
            .then(function (res) {
                if (res._status === 401) SL.redirect('/login');
            });
    }

    function loadScholarships() {
        var container = document.getElementById('org-scholarships-list');
        if (!container) return;

        container.innerHTML = '<p class="loading">Loading...</p>';

        SL.get('scholarships?limit=10')
            .then(function (res) {
                if (res._status === 401) SL.redirect('/login');
                if (res._status >= 400) {
                    container.innerHTML = '<p class="empty-state">No scholarships found.</p>';
                    return;
                }

                var scholarships = res.data || [];
                if (scholarships.length === 0) {
                    container.innerHTML = '<p class="empty-state">You haven\'t created any scholarships yet. <a href="/scholarships/create">Create one</a>.</p>';
                    return;
                }

                container.innerHTML = '<table class="data-table"><thead><tr>' +
                    '<th>Title</th><th>Category</th><th>Amount</th><th>Status</th>' +
                    '<th>Deadline</th><th>Applications</th><th>Actions</th>' +
                    '</tr></thead><tbody>' + scholarships.map(function (s) {
                    var statusLabel = s.status.charAt(0).toUpperCase() + s.status.slice(1);
                    var appCount = s.application_count || 0;
                    return '<tr>' +
                        '<td>' + SL.escapeHtml(s.title) + '</td>' +
                        '<td>' + SL.escapeHtml(s.category_name) + '</td>' +
                        '<td>' + SL.formatAmount(s.amount) + '</td>' +
                        '<td><span class="badge badge-' + s.status + '">' + statusLabel + '</span></td>' +
                        '<td>' + SL.formatDate(s.deadline) + '</td>' +
                        '<td>' + appCount + '</td>' +
                        '<td>' +
                        '<a href="/admin/scholarships/' + s.id + '" class="btn btn-sm btn-outline">Edit</a>' +
                        '<a href="/applications/' + s.id + '" class="btn btn-sm btn-outline">Reviews</a>' +
                        '</td>' +
                        '</tr>';
                }).join('') + '</tbody></table>';
            });
    }

    function loadApplications() {
        var container = document.getElementById('org-applications-list');
        if (!container) return;

        container.innerHTML = '<p class="loading">Loading...</p>';

        SL.get('applications?limit=10')
            .then(function (res) {
                if (res._status === 401) SL.redirect('/login');
                if (res._status >= 400) {
                    container.innerHTML = '<p class="empty-state">No applications yet.</p>';
                    return;
                }

                var apps = res.data || [];
                if (apps.length === 0) {
                    container.innerHTML = '<p class="empty-state">No new applications.</p>';
                    return;
                }

                container.innerHTML = '<table class="data-table"><thead><tr>' +
                    '<th>Student</th><th>Scholarship</th><th>Amount</th>' +
                    '<th>Status</th><th>Submitted</th><th>Actions</th>' +
                    '</tr></thead><tbody>' + apps.map(function (app) {
                    var statusLabel = app.status.replace('_', ' ');
                    return '<tr>' +
                        '<td>' + SL.escapeHtml(app.student_first_name + ' ' + app.student_last_name) + '</td>' +
                        '<td>' + SL.escapeHtml(app.scholarship_title) + '</td>' +
                        '<td>' + SL.formatAmount(app.amount) + '</td>' +
                        '<td><span class="status-badge status-' + app.status + '">' + statusLabel + '</span></td>' +
                        '<td>' + SL.formatDateTime(app.submitted_at) + '</td>' +
                        '<td><button class="btn btn-sm btn-primary" onclick="window.ScholarLink.reviewApplication(' + app.id + ')">Review</button></td>' +
                        '</tr>';
                }).join('') + '</tbody></table>';
            });
    }

    SL.reviewApplication = function (applicationId) {
        var modal = document.getElementById('review-modal');
        if (!modal) return;

        SL.get('applications/' + applicationId)
            .then(function (res) {
                if (res._status >= 400) return;
                var app = res.data;
                var content = '<h3>' + SL.escapeHtml(app.scholarship_title) + ' - ' +
                    SL.escapeHtml(app.student_first_name + ' ' + app.student_last_name) + '</h3>' +
                    '<p><strong>Current Status:</strong> ' + app.status.replace('_', ' ') + '</p>' +
                    '<form id="review-form">' +
                    '<div class="form-group">' +
                    '<label>Update Status</label>' +
                    '<select name="status">' +
                    '<option value="under_review">Under Review</option>' +
                    '<option value="approved">Approve</option>' +
                    '<option value="declined">Decline</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label>Notes (optional)</label>' +
                    '<textarea name="notes" rows="4" placeholder="Add review notes..."></textarea>' +
                    '</div>' +
                    '<button type="button" class="btn btn-primary" onclick="window.ScholarLink.saveReview(' + applicationId + ', ' + app.id + ')">Save</button>' +
                    '</form>';

                document.getElementById('review-modal-body').innerHTML = content;
                modal.style.display = 'block';
            });
    };

    SL.saveReview = function (applicationId, actualAppId) {
        var status = document.querySelector('#review-form select[name="status"]').value;
        var notes = document.querySelector('#review-form textarea[name="notes"]').value;

        SL.patch('applications/' + actualAppId + '/status', { status: status, notes: notes })
            .then(function (res) {
                if (res._status >= 400) {
                    SL.showError(res.error || 'Failed to update');
                } else {
                    SL.showSuccess('Application status updated');
                    document.getElementById('review-modal').style.display = 'none';
                    loadApplications();
                }
            });
    };

    SL.initScholarshipForm = function () {
        var form = document.getElementById('create-scholarship-form');
        if (!form) return;

        var categorySelect = document.getElementById('category_id');
        SL.get('categories')
            .then(function (res) {
                if (res._status >= 400) return;
                var html = '<option value="">Select a category</option>';
                (res.data || []).forEach(function (c) {
                    html += '<option value="' + c.id + '">' + SL.escapeHtml(c.name) + '</option>';
                });
                categorySelect.innerHTML = html;
            });

        var reqCount = 1;
        var addBtn = document.getElementById('add-requirement');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var container = document.getElementById('requirements-list');
                var item = document.createElement('div');
                item.className = 'requirement-item';
                item.innerHTML = '<select name="requirements[' + reqCount + '][requirement_type]">' +
                    '<option value="transcript">Official Transcript</option>' +
                    '<option value="recommendation">Letter of Recommendation</option>' +
                    '<option value="essay">Personal Essay</option>' +
                    '<option value="portfolio">Portfolio / Work Samples</option>' +
                    '<option value="financial_info">Financial Information</option>' +
                    '<option value="identification">Identification Document</option>' +
                    '<option value="other">Other Document</option>' +
                    '</select>' +
                    '<input type="text" name="requirements[' + reqCount + '][label]" placeholder="Document name" required>' +
                    '<textarea name="requirements[' + reqCount + '][description]" placeholder="Description"></textarea>' +
                    '<label class="checkbox-label"><input type="checkbox" name="requirements[' + reqCount + '][is_required]" checked> Required</label>' +
                    '<button type="button" class="btn btn-sm btn-outline remove-req">Remove</button>';
                container.appendChild(item);
                reqCount++;
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-req')) {
                    e.target.closest('.requirement-item').remove();
                }
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(form);
            formData.append('csrf_token', SL.config.csrfToken);
            formData.append('_method', 'POST');

            SL.postForm('scholarships', formData)
                .then(function (res) {
                    if (res._status === 201) {
                        SL.showSuccess('Scholarship created successfully');
                        SL.redirect('/scholarships/' + res.data.id);
                    } else if (res._status >= 400) {
                        SL.showError(res.error || 'Failed to create scholarship');
                    }
                });
        });
    };

})();
