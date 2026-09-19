// ScholarLink - Applications Module

(function () {
    'use strict';

    var SL = window.ScholarLink;

    SL.initApplicationsPage = function () {
        loadApplications();
    };

    function loadApplications() {
        var tbody = document.getElementById('applications-tbody');
        if (!tbody) return;

        SL.get('applications?limit=50')
            .then(function (res) {
                if (res._status === 401) {
                    SL.redirect('/login');
                    return;
                }
                if (res._status >= 400) {
                    tbody.innerHTML = '<tr><td colspan="6" class="loading">Failed to load applications</td></tr>';
                    return;
                }

                var apps = res.data || [];
                if (apps.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="loading">No applications yet. <a href="/scholarships">Browse scholarships</a>.</td></tr>';
                    return;
                }

                tbody.innerHTML = apps.map(function (app) {
                    var statusLabel = app.status.replace('_', ' ');
                    var canApply = app.status === 'started';
                    var actions = '';

                    if (canApply) {
                        actions = '<a href="/scholarships/' + app.scholarship_id + '/apply" class="btn btn-sm btn-primary">Continue Application</a>';
                    } else {
                        actions = '<a href="/applications/' + app.id + '" class="btn btn-sm btn-outline">View Details</a>';
                    }

                    return '<tr>' +
                        '<td>' + SL.escapeHtml(app.scholarship_title || app.title) + '</td>' +
                        '<td>' + SL.escapeHtml(app.organization_name) + '</td>' +
                        '<td>' + SL.formatAmount(app.amount) + '</td>' +
                        '<td><span class="status-badge status-' + app.status + '">' + statusLabel + '</span></td>' +
                        '<td>' + SL.formatDateTime(app.submitted_at) + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>';
                }).join('');
            });
    }

    SL.applyToScholarship = function (scholarshipId) {
        SL.post('applications', { scholarship_id: scholarshipId })
            .then(function (res) {
                if (res._status === 201) {
                    SL.showSuccess('Application created! Continue editing your application.');
                    SL.redirect('/scholarships/' + scholarshipId + '/apply');
                } else if (res._status === 400) {
                    SL.showError('You have already applied to this scholarship.');
                    SL.redirect('/applications/' + (res.data && res.data.id || scholarshipId));
                } else if (res._status >= 400) {
                    SL.showError(res.error || 'Failed to apply');
                }
            });
    };

    SL.loadApplicationDetail = function (applicationId) {
        var container = document.getElementById('application-detail-body') ||
                       document.getElementById('application-detail');
        if (!container) return;

        container.innerHTML = '<p class="loading">Loading...</p>';

        SL.get('applications/' + applicationId)
            .then(function (res) {
                if (res._status >= 400) {
                    container.innerHTML = '<p class="empty-state">Application not found.</p>';
                    return;
                }

                var app = res.data;
                var reqsHtml = (app.requirements || []).map(function (req) {
                    var reqStatusLabel = req.status.charAt(0).toUpperCase() + req.status.slice(1);
                    var uploadLink = '';
                    if (req.document_id && req.file_name) {
                        uploadLink = '<a href="/api/documents/download/' + req.document_id + '" class="btn btn-sm btn-outline">Download</a>';
                    }
                    var uploadBtn = req.status === 'missing' ?
                        '<button class="btn btn-sm btn-primary" onclick="window.ScholarLink.uploadDocument(' + app.id + ', ' + req.requirement_id + ')">Upload</button>' :
                        uploadLink || '<span class="status-badge status-' + req.status + '">' + reqStatusLabel + '</span>';

                    return '<div class="requirement-item">' +
                        '<div class="requirement-info">' +
                        '<strong>' + SL.escapeHtml(req.label) + '</strong>' +
                        '<div class="requirement-desc">' + SL.escapeHtml(req.description || '') + '</div>' +
                        '<span class="status-badge status-' + req.status + '">' + reqStatusLabel + '</span>' +
                        '</div>' +
                        uploadBtn +
                        '</div>';
                }).join('');

                container.innerHTML = '<div class="application-detail-view">' +
                    '<h2>' + SL.escapeHtml(app.scholarship_title) + '</h2>' +
                    '<p><strong>Organization:</strong> ' + SL.escapeHtml(app.organization_name || '') + '</p>' +
                    '<p><strong>Amount:</strong> ' + SL.formatAmount(app.amount) + '</p>' +
                    '<p><strong>Deadline:</strong> ' + SL.formatDate(app.deadline) + '</p>' +
                    '<p><strong>Status:</strong> <span class="status-badge status-' + app.status + '">' +
                    app.status.replace('_', ' ') + '</span></p>' +
                    '<div class="requirement-section">' +
                    '<h3>Required Documents</h3>' +
                    '<div class="requirements-list">' + reqsHtml + '</div>' +
                    '</div>' +
                    (app.status === 'started' ? '<button class="btn btn-primary" onclick="window.ScholarLink.submitApplication(' + app.id + ')">Submit Application</button>' : '') +
                    '</div>';
            });
    };

    SL.uploadDocument = function (applicationId, requirementId) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.pdf,.jpg,.jpeg,.png';
        input.onchange = function (e) {
            var file = e.target.files[0];
            if (!file) return;

            var formData = new FormData();
            formData.append('file', file);
            formData.append('application_id', applicationId);
            formData.append('requirement_id', requirementId);
            formData.append('csrf_token', SL.config.csrfToken);

            SL.postForm('documents/upload', formData)
                .then(function (res) {
                    if (res._status === 201) {
                        SL.showSuccess('Document uploaded successfully');
                        SL.loadApplicationDetail(applicationId);
                    } else {
                        SL.showError(res.error || 'Upload failed');
                    }
                });
        };
        input.click();
    };

    SL.submitApplication = function (applicationId) {
        if (!confirm('Are you sure you want to submit this application? You won\'t be able to make changes after submission.')) {
            return;
        }

        SL.patch('applications/' + applicationId + '/status', { status: 'under_review' })
            .then(function (res) {
                if (res._status >= 400) {
                    SL.showError(res.error || 'Failed to submit');
                } else {
                    SL.showSuccess('Application submitted successfully');
                    SL.redirect('/applications/' + applicationId);
                }
            });
    };

})();
