/* bayawan-mini-hotel-system/scripts/user_csrf.js
 *
 * Reads the CSRF token from <meta name="csrf-token"> and automatically
 * injects it into every XHR and fetch() POST made on the page.
 *
 * Must be loaded BEFORE any other user JS scripts.
 */

(function () {
    const metaTag    = document.querySelector('meta[name="csrf-token"]');
    const CSRF_TOKEN = metaTag ? metaTag.getAttribute('content') : '';

    if (!CSRF_TOKEN) {
        console.warn('[CSRF] No csrf-token meta tag found. AJAX requests will fail validation.');
        return;
    }

    // ── Patch XMLHttpRequest ──────────────────────────────────────────
    const origOpen      = XMLHttpRequest.prototype.open;
    const origSend      = XMLHttpRequest.prototype.send;
    const origSetHeader = XMLHttpRequest.prototype.setRequestHeader;

    XMLHttpRequest.prototype.setRequestHeader = function (name, value) {
        if (name.toLowerCase() === 'content-type') {
            this._contentType = value;
        }
        origSetHeader.call(this, name, value);
    };

    XMLHttpRequest.prototype.open = function (method) {
        this._method = method ? method.toUpperCase() : 'GET';
        origOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function (body) {
        if (this._method === 'POST') {
            if (body instanceof FormData) {
                body.append('csrf_token', CSRF_TOKEN);
                origSend.call(this, body);
            } else if (typeof body === 'string' && body.length > 0) {
                origSend.call(this, body + '&csrf_token=' + encodeURIComponent(CSRF_TOKEN));
            } else {
                origSend.call(this, 'csrf_token=' + encodeURIComponent(CSRF_TOKEN));
            }
        } else {
            origSend.call(this, body);
        }
    };

    // ── Patch fetch() ─────────────────────────────────────────────────
    const origFetch = window.fetch;
    window.fetch = function (resource, init) {
        init = init || {};
        const method = (init.method || 'GET').toUpperCase();

        if (method !== 'POST') {
            return origFetch.call(this, resource, init);
        }

        const body = init.body;

        if (body instanceof FormData) {
            body.append('csrf_token', CSRF_TOKEN);
        } else if (typeof body === 'string') {
            init.body = body + (body.length ? '&' : '') + 'csrf_token=' + encodeURIComponent(CSRF_TOKEN);
        } else if (!body) {
            init.body = 'csrf_token=' + encodeURIComponent(CSRF_TOKEN);
        }

        return origFetch.call(this, resource, init);
    };

})();
