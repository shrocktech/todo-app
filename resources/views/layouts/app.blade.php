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

            // Helper to register event listeners for a single todo list item (li element)
            function registerTodoItem(li) {
                if (!li) return;

                // Toggle form
                const toggleForm = li.querySelector('form.todo-toggle-form');
                if (toggleForm) {
                    const checkbox = toggleForm.querySelector('input[type="checkbox"][name="completed"]');
                    if (checkbox && !checkbox._hasListener) {
                        checkbox._hasListener = true;
                        checkbox.addEventListener('change', function (ev) {
                            const form = toggleForm;
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
                                    const id = form.closest('li') ? form.closest('li').getAttribute('data-todo-id') : null;
                                    const items = id ? document.querySelectorAll('li[data-todo-id="' + id + '"]') : (form.closest('li') ? [form.closest('li')] : []);
                                    if (data && data.success) {
                                        items.forEach(function(li) {
                                            const titleEl = li.querySelector('.todo-title');
                                            const toggleCheckbox = li.querySelector('input[type="checkbox"][name="completed"]');
                                            if (titleEl) {
                                                if (data.completed) titleEl.classList.add('completed');
                                                else titleEl.classList.remove('completed');
                                            }
                                            if (toggleCheckbox) toggleCheckbox.checked = data.completed;
                                        });
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
                    }
                }

                // Delete form
                const deleteForm = li.querySelector('form.todo-delete-form');
                if (deleteForm && !deleteForm._hasListener) {
                    deleteForm._hasListener = true;
                    deleteForm.addEventListener('submit', function (ev) {
                        ev.preventDefault();
                        // No confirm dialog: schedule delete immediately (undo available)
                        scheduleDelete(li, deleteForm);
                    });
                }

                // Edit button
                const editBtn = li.querySelector('.todo-edit-btn');
                if (editBtn && !editBtn._hasListener) {
                    editBtn._hasListener = true;
                    editBtn.addEventListener('click', function () {
                        const title = editBtn.getAttribute('data-title') || '';
                        const completed = editBtn.getAttribute('data-completed') === '1';
                        currentAction = editBtn.getAttribute('data-action');
                        if (titleInput) titleInput.value = title;
                        if (completedInput) completedInput.checked = completed;
                        if (bsModal) bsModal.show();
                    });
                }
            }

            // Register existing items
            document.querySelectorAll('li[data-todo-id]').forEach(function(li) { registerTodoItem(li); });

            // Delete with undo: do not immediately call server; show undo toast and delay actual delete
            const pendingDeletes = new Map();

            function scheduleDelete(li, form) {
                const id = li.getAttribute('data-todo-id');
                // find all matching items
                const items = id ? Array.from(document.querySelectorAll('li[data-todo-id="' + id + '"]')) : [li];
                // visually fade all
                items.forEach(function(i) { i.classList.add('fading'); });

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
                            // remove all matching elements from DOM
                            items.forEach(function(i) { if (i) i.remove(); });
                            showToast('Deleted', 'Todo removed', 'success');
                        } else {
                            // restore on failure
                            items.forEach(function(i) { if (i) i.classList.remove('fading'); });
                            showToast('Error', 'Could not delete todo', 'danger');
                        }
                    }).catch(err => {
                        items.forEach(function(i) { if (i) i.classList.remove('fading'); });
                        showToast('Network', 'Network error deleting todo', 'danger');
                    }).finally(() => {
                        pendingDeletes.delete(id);
                        if (toastEl) toastEl.remove();
                    });
                }, 5000); // 5s undo window

                pendingDeletes.set(id, { timer, items, form, toastEl });
            }

            function cancelPendingDelete(id) {
                const rec = pendingDeletes.get(id);
                if (!rec) return false;
                clearTimeout(rec.timer);
                if (rec.items && Array.isArray(rec.items)) {
                    rec.items.forEach(function(i) { if (i) i.classList.remove('fading'); });
                } else if (rec.li) {
                    rec.li.classList.remove('fading');
                }
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

            // Note: delete handlers are registered per-list-item in `registerTodoItem`.
            // We intentionally do not add a global submit handler here to avoid duplicate prompts
            // and duplicate scheduling of deletes.

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

            // Quick-add form handling
            const quickAddForm = document.getElementById('quick-add-form');
            const quickAddInput = document.getElementById('quick-add-input');
            if (quickAddForm && quickAddInput) {
                quickAddForm.addEventListener('submit', function (ev) {
                    ev.preventDefault();
                    const title = quickAddInput.value.trim();
                    if (!title) return;
                    const formData = new FormData();
                    formData.append('title', title);

                    fetch('{{ route('todos.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    }).then(resp => resp.json()).then(data => {
                        if (data && data.success && data.todo) {
                            const todo = data.todo;
                            // Build li markup (minimal, consistent with server-side markup)
                            const liHtml = `
                                <li class="list-group-item d-flex justify-content-between align-items-center" data-todo-id="${todo.id}">
                                    <div>
                                        <form method="POST" action="/todos/${todo.id}" style="display:inline" class="todo-toggle-form">
                                            <input type="hidden" name="_method" value="PUT">
                                            <input type="hidden" name="_token" value="${csrf}">
                                            <input type="hidden" name="completed" value="0">
                                            <input type="checkbox" name="completed" value="1" class="form-check-input me-2" style="vertical-align:middle;" ${todo.completed ? 'checked' : ''}>
                                        </form>
                                        <span class="todo-title ${todo.completed ? 'completed' : ''}">${escapeHtml(todo.title)}</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary todo-edit-btn" title="Edit" aria-label="Edit todo"
                                            data-id="${todo.id}"
                                            data-title="${escapeHtml(todo.title)}"
                                            data-completed="${todo.completed ? 1 : 0}"
                                            data-action="/todos/${todo.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l2.0 2a.5.5 0 0 1 0 .708l-9.793 9.793-2.5.5a.5.5 0 0 1-.606-.606l.5-2.5L12.146.146zM11.207 2L3 10.207V13h2.793L14 4.793 11.207 2z"/></svg>
                                        </button>
                                        <form method="POST" action="/todos/${todo.id}" style="display:inline" class="todo-delete-form">
                                            <input type="hidden" name="_token" value="${csrf}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete todo">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 5h4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5v-7zM14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 1 1 0-2H5l1-1h4l1 1h3.5a1 1 0 0 1 1 1z"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>`;

                            const list = document.querySelector('ul.list-group');
                            if (list) {
                                list.insertAdjacentHTML('afterbegin', liHtml);
                                const newLi = list.querySelector('li[data-todo-id="' + todo.id + '"]');
                                if (newLi) registerTodoItem(newLi);
                            }

                            quickAddInput.value = '';
                            quickAddInput.focus();
                        } else {
                            showToast('Error', 'Could not create todo', 'danger');
                        }
                    }).catch(err => {
                        console.error('Create error', err);
                        showToast('Network', 'Network error creating todo', 'danger');
                    });
                });
            }

            // Helper to escape HTML for insertion
            function escapeHtml(str) {
                return (str + '').replace(/[&<>"']/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]; });
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
