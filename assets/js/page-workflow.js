(function () {
    'use strict';
    var status = document.getElementById('gml-workflow-status');
    var i18n = gmlPageWorkflow.i18n;
    function task(id, state) { return i18n.task + ' #' + id + ': ' + (i18n.states[state] || state); }
    function show(message) { if (status) status.textContent = message; }
    async function send(data) {
        data.set('action', 'gml_page_workflow');
        data.set('nonce', gmlPageWorkflow.nonce);
        var response = await fetch(gmlPageWorkflow.url, {method:'POST', body:data, credentials:'same-origin'});
        var result = await response.json();
        if (!result.success) throw new Error(result.data.message || i18n.failed);
        return result.data;
    }
    async function poll(id, count) {
        var data = new FormData(); data.set('operation','status');
        try {
            var job = await send(data);
            if (Number(job.id) !== Number(id)) return show(i18n.changed);
            show(task(id, job.state) + (job.error ? ' / ' + job.error : '') + (job.circuit ? ' / ' + i18n.provider : ''));
            if (job.state === 'candidate') {
                var field = document.querySelector('[data-queue-id="' + Number(id) + '"] textarea');
                if (field) field.value = job.candidate;
                show(task(id, job.state) + '. ' + i18n.candidate);
                return;
            }
            if (job.state === 'saved') { window.location.reload(); return; }
            if (['failed','expired'].indexOf(job.state) >= 0) return;
            if (count >= 60) return show(task(id, job.state) + '. ' + i18n.waiting);
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
        show(i18n.submitting);
        try {
            var result = await send(data);
            if (result.snapshot && form.closest('[data-queue-id]')) {
                form.closest('[data-queue-id]').querySelectorAll('[name="snapshot"]').forEach(function (field) { field.value = result.snapshot; });
            }
            show(result.message || task(result.id, result.state));
            if (result.reload) { window.location.reload(); return; }
            if (data.get('operation') === 'ai') poll(result.id, 0);
        } catch (error) { show(error.message); }
        finally { buttons.forEach(function (button) { button.disabled = false; }); }
    });
}());
