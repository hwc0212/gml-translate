/* First-party aggregate demand, only after site consent. No AI or resource creation. */
(function () {
    'use strict';
    var sent = false;
    function count() {
        if (sent || !window.gmlPageDemand || document.visibilityState !== 'visible') return;
        var consent = typeof window.wp_has_consent === 'function' && window.wp_has_consent('statistics');
        if (!consent && window.gmlPageDemandConsent !== true) return;
        var data = window.gmlPageDemand;
        var key = 'gml-demand:' + data.resource + ':' + data.language;
        try {
            if (Date.now() - Number(sessionStorage.getItem(key)) < 1800000) return;
            sessionStorage.setItem(key, String(Date.now()));
        } catch (ignore) { /* Server-side limits remain authoritative. */ }
        sent = true;
        fetch(data.endpoint, {
            method: 'POST', credentials: 'omit', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data), keepalive: true
        }).catch(function () { /* Demand failure never interrupts navigation. */ });
    }
    document.addEventListener('gml:consent-granted', count);
    document.addEventListener('wp_listen_for_consent_change', count);
    document.addEventListener('visibilitychange', count);
    count();
}());
