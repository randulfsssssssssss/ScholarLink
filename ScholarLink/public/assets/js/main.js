// ScholarLink - API Client

(function (window) {
    'use strict';

    var ScholarLink = {
        config: window.APP_CONFIG || {
            baseUrl: '/api/v1',
            csrfToken: '',
            userRole: 'guest',
            userId: null
        },

        _request: function (method, url, data, isFormData) {
            var headers = {
                'X-CSRF-Token': ScholarLink.config.csrfToken,
                'Accept': 'application/json'
            };

            var options = {
                method: method,
                headers: headers,
                credentials: 'same-origin'
            };

            if (data !== null && data !== undefined) {
                if (isFormData) {
                    options.body = data;
                } else {
                    headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(data);
                }
            }

            return fetch(ScholarLink.config.baseUrl + url, options)
                .then(function (response) {
                    var contentType = response.headers.get('content-type') || '';
                    if (contentType.indexOf('application/json') !== -1) {
                        return response.json().then(function (json) {
                            json._status = response.status;
                            return json;
                        });
                    }
                    return response.text().then(function (text) {
                        return { _status: response.status, _text: text };
                    });
                });
        },

        get: function (url) {
            return ScholarLink._request('GET', url, null, false);
        },

        post: function (url, data) {
            return ScholarLink._request('POST', url, data, false);
        },

        put: function (url, data) {
            return ScholarLink._request('PUT', url, data, false);
        },

        patch: function (url, data) {
            return ScholarLink._request('PATCH', url, data, false);
        },

        delete: function (url) {
            return ScholarLink._request('DELETE', url, null, false);
        },

        postForm: function (url, formData) {
            return ScholarLink._request('POST', url, formData, true);
        },

        handleResponse: function (response) {
            if (response._status >= 400) {
                var error = response.error || 'Request failed';
                ScholarLink.showError(error);
                throw new Error(error);
            }
            return response;
        },

        showError: function (message) {
            var existing = document.querySelector('.alert-global');
            if (existing) {
                existing.remove();
            }
            var alert = document.createElement('div');
            alert.className = 'alert alert-error alert-global';
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '1001';
            alert.style.maxWidth = '400px';
            alert.textContent = message;
            document.body.appendChild(alert);
            setTimeout(function () {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        },

        showSuccess: function (message) {
            var existing = document.querySelector('.alert-global-success');
            if (existing) {
                existing.remove();
            }
            var alert = document.createElement('div');
            alert.className = 'alert alert-success alert-global-success';
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '1001';
            alert.style.maxWidth = '400px';
            alert.textContent = message;
            document.body.appendChild(alert);
            setTimeout(function () {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        },

        redirect: function (path) {
            window.location.href = path;
        },

        formatAmount: function (amount) {
            return '$' + (parseFloat(amount) || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatDate: function (dateStr) {
            if (!dateStr) return 'N/A';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return 'N/A';
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        formatDateTime: function (dateStr) {
            if (!dateStr) return 'N/A';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return 'N/A';
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        escapeHtml: function (text) {
            if (text === null || text === undefined) return '';
            var div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        },

        isPastDeadline: function (deadline) {
            if (!deadline) return false;
            var d = new Date(deadline);
            var now = new Date();
            return d < now;
        }
    };

    window.ScholarLink = ScholarLink;

})(window);
