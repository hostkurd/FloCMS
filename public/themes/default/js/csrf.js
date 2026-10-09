/**
 * Adds the CSRF token to AJAX requests that change state.
 *
 * Reads <meta name="csrf-token"> (rendered by the admin layout) and sends it
 * as the X-CSRF-TOKEN header on same-origin POST/PUT/PATCH/DELETE requests
 * made with fetch() or jQuery. For other clients use FloCsrf.token() or
 * FloCsrf.headers().
 */
(function (window, document) {
    'use strict';

    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function changesState(method) {
        return !/^(GET|HEAD|OPTIONS|TRACE)$/i.test(method || 'GET');
    }

    function sameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    window.FloCsrf = {
        token: token,
        headers: function () {
            return { 'X-CSRF-TOKEN': token() };
        }
    };

    if (typeof window.fetch === 'function') {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var url = typeof input === 'string' ? input : (input && input.url) || '';
            var method = init.method || (input && input.method) || 'GET';

            if (changesState(method) && sameOrigin(url)) {
                var headers = new Headers(init.headers || (input && input.headers) || {});
                if (!headers.has('X-CSRF-TOKEN')) {
                    headers.set('X-CSRF-TOKEN', token());
                }
                init = Object.assign({}, init, { headers: headers });
            }

            return nativeFetch.call(this, input, init);
        };
    }

    function setupJquery() {
        var $ = window.jQuery;
        if (!$ || !$.ajaxPrefilter) {
            return false;
        }

        $.ajaxPrefilter(function (options, original, xhr) {
            if (changesState(options.type || options.method) && sameOrigin(options.url)) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token());
            }
        });
        return true;
    }

    // jQuery may load after this script
    if (!setupJquery()) {
        document.addEventListener('DOMContentLoaded', setupJquery);
    }
})(window, document);
