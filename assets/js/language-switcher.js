/* GML Language Switcher - Frontend JS */
(function () {
    'use strict';

    /* ── 1. Dropdown toggle ── */
    function initDropdowns() {
        document.querySelectorAll('.gml-style-dropdown .gml-dropdown').forEach(function (dropdown) {
            var btn = dropdown.querySelector('.gml-dropdown-btn');
            if (!btn) return;

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = dropdown.classList.contains('open');
                document.querySelectorAll('.gml-dropdown.open').forEach(function (d) {
                    d.classList.remove('open');
                    var b = d.querySelector('.gml-dropdown-btn');
                    if (b) b.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    dropdown.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.gml-dropdown.open').forEach(function (d) {
                d.classList.remove('open');
                var b = d.querySelector('.gml-dropdown-btn');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.gml-dropdown.open').forEach(function (d) {
                    d.classList.remove('open');
                });
            }
        });
    }

    /* ── 2. Internal link rewriting ── */
    function initLinkRewriting() {
        // gmlData is injected by wp_localize_script
        if (typeof gmlData === 'undefined') return;

        var currentLang = gmlData.currentLang;
        var sourceLang  = gmlData.sourceLang;
        var languages   = gmlData.languages;   // array of all lang codes
        var homeUrl     = gmlData.homeUrl;      // e.g. "https://example.com/"

        // Only rewrite when browsing a non-source language
        if (!currentLang || currentLang === sourceLang) return;
        // Only rewrite if currentLang is a known configured language
        if (languages.indexOf(currentLang) === -1) return;

        var prefix = '/' + currentLang + '/';

        // Parse origin from homeUrl
        var homeOrigin = homeUrl.replace(/\/$/, ''); // strip trailing slash

        /**
         * Returns true if href is an internal link that should get the prefix.
         */
        function shouldRewrite(href) {
            if (!href) return false;
            // Skip non-http schemes
            if (/^(mailto:|tel:|javascript:|#)/i.test(href)) return false;
            // Skip hash-only
            if (href.charAt(0) === '#') return false;

            var path;
            if (/^https?:\/\//i.test(href)) {
                // Absolute URL — must be same origin
                if (href.indexOf(homeOrigin) !== 0) return false;
                path = href.slice(homeOrigin.length) || '/';
            } else if (href.charAt(0) === '/') {
                path = href;
            } else {
                // Relative path — treat as internal
                path = '/' + href;
            }

            // Skip admin / login paths
            if (/^\/(wp-admin|wp-login\.php)/i.test(path)) return false;
            // Skip paths that already have a language prefix
            var langPattern = new RegExp('^/(' + languages.join('|') + ')(/|$)');
            if (langPattern.test(path)) return false;

            return true;
        }

        /**
         * Rewrite a single href string.
         */
        function rewriteHref(href) {
            if (/^https?:\/\//i.test(href)) {
                // Absolute: insert prefix after origin
                var path = href.slice(homeOrigin.length) || '/';
                if (path.charAt(0) !== '/') path = '/' + path;
                return homeOrigin + prefix + path.replace(/^\//, '');
            }
            // Root-relative or relative
            var p = href.charAt(0) === '/' ? href : '/' + href;
            return prefix + p.replace(/^\//, '');
        }

        /**
         * Rewrite all <a href> and <form action> in a given root element.
         * Skips links inside the GML language switcher (those are already correct).
         */
        function rewriteLinks(root) {
            root.querySelectorAll('a[href]').forEach(function (a) {
                // Never rewrite language switcher links — they point to the correct URL already
                if (a.closest('.gml-language-switcher')) return;
                var href = a.getAttribute('href');
                if (shouldRewrite(href)) {
                    a.setAttribute('href', rewriteHref(href));
                }
            });
            root.querySelectorAll('form[action]').forEach(function (form) {
                var action = form.getAttribute('action');
                if (shouldRewrite(action)) {
                    form.setAttribute('action', rewriteHref(action));
                }
            });
        }

        // Initial pass on full document
        rewriteLinks(document);

        // Watch for dynamically added content (mega-menus, AJAX, etc.)
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) { // ELEMENT_NODE
                            rewriteLinks(node);
                        }
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    /* ── Init ── */
    function init() {
        initDropdowns();
        initLinkRewriting();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
