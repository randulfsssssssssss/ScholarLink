// ScholarLink - Vendor utilities (polyfills and helpers)

// Polyfill for async/await support where needed
(function () {
    'use strict';

    if (!window.Promise) {
        window.Promise = function (executor) {
            var instance = this;
            var state = 'pending';
            var value = undefined;
            var callbacks = [];

            function resolve(value) {
                if (state === 'pending') {
                    state = 'fulfilled';
                    instance.value = value;
                    callbacks.forEach(function (cb) { cb(value); });
                }
            }

            function reject(reason) {
                if (state === 'pending') {
                    state = 'rejected';
                    instance.reason = reason;
                    callbacks.forEach(function (cb) { cb(); });
                }
            }

            executor(resolve, reject);
        };
    }
})();
