<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>To-Do App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .todo-title.completed { text-decoration: line-through; color: #6c757d; }
        .list-group-item { transition: opacity .25s ease, transform .25s ease; }
        .list-group-item.fading { opacity: 0; transform: translateY(-8px); }
        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 1080; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">To-Do App</a>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    {{-- Toast container for non-blocking notifications --}}
    <div class="toast-container" id="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Small helper to submit toggle forms via AJAX and handle delete with undo
        document.addEventListener('DOMContentLoaded', function () {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Toggle completed
            document.querySelectorAll('form.todo-toggle-form').forEach(function (form) {
                const checkbox = form.querySelector('input[type="checkbox"][name="completed"]');
                if (!checkbox) return;

                checkbox.addEventListener('change', function (ev) {
                    const formData = new FormData();
                    form.querySelectorAll('input[name], select[name], textarea[name]').forEach(function (el) {
                        if (el.type === 'checkbox') {
                            if (el.checked) formData.append(el.name, el.value);
                            else formData.append(el.name, '0');
                        } else {
                            formData.append(el.name, el.value);
                        }
                    });

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    }).then(resp => resp.json()).then(data => {
                        const li = form.closest('li');
                        const titleEl = li ? li.querySelector('.todo-title') : null;
                        if (data && data.success) {
                            if (titleEl) {
                                if (data.completed) titleEl.classList.add('completed');
                                else titleEl.classList.remove('completed');
                            }
                            showToast('Saved', 'Todo updated', 'success');
                        } else {
                            checkbox.checked = !checkbox.checked;
                            console.error('Toggle failed', data);
                            showToast('Error', 'Could not update todo', 'danger');
                        }
                    }).catch(err => {
                        checkbox.checked = !checkbox.checked;
                        console.error('Toggle error', err);
                        showToast('Network', 'Network error updating todo', 'danger');
                    });
                });
            });

            // Delete with undo: do not immediately call server; show undo toast and delay actual delete
            const pendingDeletes = new Map();

            function scheduleDelete(li, form) {
                const id = li.getAttribute('data-todo-id');
                // visually fade
                li.classList.add('fading');

                // show toast with Undo
                const toastEl = createUndoToast(id);

                const timer = setTimeout(() => {
                    // perform server delete
                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    }).then(resp => resp.json()).then(data => {
                        if (data && data.success) {
                            // remove element from DOM
                            if (li) li.remove();
                            showToast('Deleted', 'Todo removed', 'success');
                        } else {
                            // restore on failure
                            if (li) li.classList.remove('fading');
                            showToast('Error', 'Could not delete todo', 'danger');
                        }
                    }).catch(err => {
                        if (li) li.classList.remove('fading');
                        showToast('Network', 'Network error deleting todo', 'danger');
                    }).finally(() => {
                        pendingDeletes.delete(id);
                        if (toastEl) toastEl.remove();
                    });
                }, 5000); // 5s undo window

                pendingDeletes.set(id, { timer, li, form, toastEl });
            }

            function cancelPendingDelete(id) {
                const rec = pendingDeletes.get(id);
                if (!rec) return false;
                clearTimeout(rec.timer);
                if (rec.li) rec.li.classList.remove('fading');
                if (rec.toastEl) rec.toastEl.remove();
                pendingDeletes.delete(id);
                showToast('Restored', 'Delete undone', 'secondary');
                return true;
            }

            function createUndoToast(id) {
                const container = document.getElementById('toast-container');
                if (!container) return null;
                const toastId = 'undo-toast-' + id + '-' + Date.now();
                const html = `
                    <div id="${toastId}" class="toast align-items-center text-bg-warning border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                      <div class="d-flex">
                        <div class="toast-body">Item deleted — <button class="btn btn-sm btn-link p-0" data-undo-id="${id}">Undo</button></div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                      </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', html);
                const el = document.getElementById(toastId);
                const bsToast = new bootstrap.Toast(el, { delay: 5000 });
                bsToast.show();
                el.querySelector('[data-undo-id]')?.addEventListener('click', function (ev) {
                    const undoId = this.getAttribute('data-undo-id');
                    cancelPendingDelete(undoId);
                });
                el.addEventListener('hidden.bs.toast', () => el.remove());
                return el;
            }

            document.querySelectorAll('form.todo-delete-form').forEach(function (delForm) {
                delForm.addEventListener('submit', function (ev) {
                    ev.preventDefault();
                    if (!confirm('Delete this todo?')) return;
                    const li = delForm.closest('li');
                    // schedule delete with undo
                    scheduleDelete(li, delForm);
                });
            });

            // Edit modal handling
            const editModalEl = document.getElementById('todoEditModal');
            const bsModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
            const titleInput = document.getElementById('todo-edit-title');
            const completedInput = document.getElementById('todo-edit-completed');
            const saveBtn = document.getElementById('todo-edit-save');
            let currentAction = null;

            document.querySelectorAll('.todo-edit-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const title = btn.getAttribute('data-title') || '';
                    const completed = btn.getAttribute('data-completed') === '1';
                    currentAction = btn.getAttribute('data-action');
                    if (titleInput) titleInput.value = title;
                    if (completedInput) completedInput.checked = completed;
                    if (bsModal) bsModal.show();
                });
            });

            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    if (!currentAction) return;
                    const formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('title', titleInput.value);
                    formData.append('completed', completedInput.checked ? '1' : '0');

                    fetch(currentAction, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    }).then(res => res.json()).then(data => {
                        if (data && data.success) {
                            // update DOM
                            const idMatch = currentAction.match(/\/([0-9]+)$/);
                            let li = null;
                            if (idMatch && idMatch[1]) li = document.querySelector('li[data-todo-id="' + idMatch[1] + '"]');
                            if (!li) li = document.querySelector('li[data-todo-id]');
                            if (li) {
                                const titleEl = li.querySelector('.todo-title');
                                const toggleCheckbox = li.querySelector('input[type="checkbox"][name="completed"]');
                                if (titleEl) titleEl.textContent = titleInput.value;
                                if (toggleCheckbox) toggleCheckbox.checked = completedInput.checked;
                                if (titleEl) {
                                    if (data.completed) titleEl.classList.add('completed');
                                    else titleEl.classList.remove('completed');
                                }
                            }
                            if (bsModal) bsModal.hide();
                            showToast('Saved', 'Todo updated', 'success');
                        } else {
                            showToast('Error', 'Could not update todo', 'danger');
                        }
                    }).catch(err => {
                        console.error('Edit error', err);
                        showToast('Network', 'Network error updating todo', 'danger');
                    });
                });
            }

            // Tiny helper to show bootstrap toasts
            function showToast(title, body, variant = 'primary') {
                const container = document.getElementById('toast-container');
                if (!container) return;
                const toastId = 'toast-' + Date.now();
                const toastHtml = `
                    <div id="${toastId}" class="toast align-items-center text-bg-${variant} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                      <div class="d-flex">
                        <div class="toast-body">
                          <strong>${title}</strong> — ${body}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                      </div>
                    </div>`;
                container.insertAdjacentHTML('beforeend', toastHtml);
                const el = document.getElementById(toastId);
                const bsToast = new bootstrap.Toast(el, { delay: 2500 });
                bsToast.show();
                el.addEventListener('hidden.bs.toast', () => el.remove());
            }

        });
    </script>
</body>
</html>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>To-Do App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">To-Do App</a>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
