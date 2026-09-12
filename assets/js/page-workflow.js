(function () {
    'use strict';
    var status = document.getElementById('gml-workflow-status');
    function show(message) { if (status) status.textContent = message; }
    async function send(data) {
        data.set('action', 'gml_page_workflow');
        data.set('nonce', gmlPageWorkflow.nonce);
        var response = await fetch(gmlPageWorkflow.url, {method:'POST', body:data, credentials:'same-origin'});
        var result = await response.json();
        if (!result.success) throw new Error(result.data.message || 'Request failed. Refresh and try again.');
        return result.data;
    }
    async function poll(id, count) {
        var data = new FormData(); data.set('operation','status');
        try {
            var job = await send(data);
            if (Number(job.id) !== Number(id)) return show('The active task changed. Refresh to review.');
            show('Task #' + id + ': ' + job.state + (job.error ? ' — ' + job.error : '') + (job.circuit ? ' / Provider configuration requires attention.' : ''));
            if (job.state === 'candidate') {
                var field = document.querySelector('[data-queue-id="' + Number(id) + '"] textarea');
                if (field) field.value = job.candidate;
                show('Task #' + id + ': candidate ready. Compare with the saved translation, then explicitly save your choice.');
                return;
            }
            if (['saved','failed','expired'].indexOf(job.state) >= 0) return;
            if (count >= 60) return show('Task #' + id + ' remains ' + job.state + '. Check WordPress Cron and provider cooldown; refresh for its current status.');
            setTimeout(function () { poll(id, count + 1); }, 3000);
        } catch (error) { show(error.message); }
    }
    document.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!form.classList.contains('gml-workflow-form')) return;
        event.preventDefault();
        var data = new FormData(form);
        var buttons = form.querySelectorAll('button');
        buttons.forEach(function (button) { button.disabled = true; });
        show('Submitting…');
        try {
            var result = await send(data);
            if (result.snapshot && form.closest('[data-queue-id]')) {
                form.closest('[data-queue-id]').querySelectorAll('[name="snapshot"]').forEach(function (field) { field.value = result.snapshot; });
            }
            show(result.message || ('Task #' + result.id + ': ' + result.state));
            if (data.get('operation') === 'ai') poll(result.id, 0);
        } catch (error) { show(error.message); }
        finally { buttons.forEach(function (button) { button.disabled = false; }); }
    });
}());
