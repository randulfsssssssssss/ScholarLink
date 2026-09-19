// ScholarLink - Scholarship Browser Module

(function () {
    'use strict';

    var SL = window.ScholarLink;
    var currentOffset = 0;
    var currentLimit = 12;
    var currentQuery = '';
    var currentCategory = '';

    SL.initScholarshipBrowser = function () {
        loadCategories();
        loadScholarships();

        var searchInput = document.getElementById('search-input');
        var searchBtn = document.getElementById('search-btn');
        var categoryFilter = document.getElementById('category-filter');

        if (searchBtn) {
            searchBtn.addEventListener('click', function () {
                currentQuery = searchInput ? searchInput.value.trim() : '';
                currentOffset = 0;
                loadScholarships();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keyup', function (e) {
                if (e.key === 'Enter') {
                    currentQuery = searchInput.value.trim();
                    currentOffset = 0;
                    loadScholarships();
                }
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', function () {
                currentCategory = categoryFilter.value;
                currentOffset = 0;
                loadScholarships();
            });
        }
    };

    function loadCategories() {
        var select = document.getElementById('category-filter');
        if (!select) return;

        SL.get('categories')
            .then(function (res) {
                if (res._status >= 400) return;
                var categories = res.data || [];
                var html = '<option value="">All Categories</option>';
                categories.forEach(function (c) {
                    html += '<option value="' + c.slug + '">' + SL.escapeHtml(c.name) + '</option>';
                });
                select.innerHTML = html;
            });
    }

    function loadScholarships() {
        var container = document.getElementById('scholarship-grid');
        var pagination = document.getElementById('pagination');
        if (!container) return;

        container.innerHTML = '<p class="loading">Loading scholarships...</p>';

        var params = '?limit=' + currentLimit + '&offset=' + currentOffset;
        if (currentQuery) params += '&search=' + encodeURIComponent(currentQuery);
        if (currentCategory) params += '&category=' + encodeURIComponent(currentCategory);

        SL.get('scholarships' + params)
            .then(function (res) {
                if (res._status >= 400) {
                    container.innerHTML = '<p class="empty-state">No scholarships found.</p>';
                    return;
                }

                var scholarships = res.data || [];
                var meta = res.meta || {};

                if (scholarships.length === 0) {
                    container.innerHTML = '<p class="empty-state">No scholarships match your search. Try adjusting your filters.</p>';
                    return;
                }

                container.innerHTML = scholarships.map(function (s) {
                    var badgeClass = s.status === 'published' ? 'badge-published' : 'badge-draft';
                    var deadlineClass = SL.isPastDeadline(s.deadline) ? 'past-deadline' : '';
                    return '<div class="scholarship-card">' +
                        '<div class="scholarship-card-header">' +
                        '<div class="title">' + SL.escapeHtml(s.title) + '</div>' +
                        '<span class="badge ' + badgeClass + '">' + SL.escapeHtml(s.status) + '</span>' +
                        '</div>' +
                        '<div class="amount">' + SL.formatAmount(s.amount) + '</div>' +
                        '<div class="category">' + SL.escapeHtml(s.category_name) + '</div>' +
                        (s.is_verified ? '<span class="badge badge-verified">Verified</span>' : '') +
                        '<div class="deadline ' + deadlineClass + '">' +
                        'Deadline: ' + SL.formatDate(s.deadline) +
                        '</div>' +
                        '<div class="description">' + SL.escapeHtml(s.description ? s.description.substring(0, 120) + '...' : '') + '</div>' +
                        '<div class="organization">By: ' + SL.escapeHtml(s.organization_name) + '</div>' +
                        '<a href="/scholarships/' + s.id + '" class="btn btn-primary btn-sm">View Details</a>' +
                        '</div>';
                }).join('');

                renderPagination(meta, pagination);
            });
    }

    function renderPagination(meta, container) {
        if (!container) return;
        var total = meta.total || 0;
        var limit = meta.limit || currentLimit;
        var pages = Math.ceil(total / limit);
        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        var html = '';
        var current = Math.floor(currentOffset / limit) + 1;

        if (current > 1) {
            html += '<button onclick="window.ScholarLink.navigateToPage(' + (current - 1) + 'prev')">&larr;</button>';
        }
        for (var i = 1; i <= pages; i++) {
            if (Math.abs(i - current) <= 2) {
                html += '<button class="' + (i === current ? 'active' : '') + '" onclick="window.ScholarLink.navigateToPage(' + i + 'prev')">' + i + '</button>';
            }
        }
        if (current < pages) {
            html += '<button onclick="window.ScholarLink.navigateToPage(' + (current + 1) + 'prev')">&rarr;</button>';
        }
        container.innerHTML = html;
    }

    SL.navigateToPage = function (page) {
        currentOffset = (page - 1) * currentLimit;
        loadScholarships();
    };

    SL.bookmarkScholarship = function (scholarshipId) {
        SL.post('bookmarks', { scholarship_id: scholarshipId })
            .then(function (res) {
                if (res._status === 201) {
                    SL.showSuccess('Scholarship bookmarked');
                } else if (res._status === 409) {
                    SL.showError('Already bookmarked');
                }
            });
    };

})();
