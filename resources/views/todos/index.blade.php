@extends('layouts.app')

@section('content')
<div class="container">
    <h1>To-Do List</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex align-items-center mb-3">
      <form id="quick-add-form" class="me-2" style="flex:1; max-width:640px;">
        @csrf
        <div class="input-group">
          <input type="text" id="quick-add-input" name="title" class="form-control" placeholder="Add a new todo and press Enter" aria-label="New todo title">
          <button class="btn btn-primary" type="submit">Create Todo</button>
        </div>
      </form>
      <!-- full-create link intentionally removed to keep quick-add only -->
    </div>

    <ul class="list-group">
        @forelse($todos as $todo)
            <li class="list-group-item d-flex justify-content-between align-items-center" data-todo-id="{{ $todo->id }}">
                <div>
                    <form method="POST" action="{{ route('todos.update', $todo) }}" style="display:inline" class="todo-toggle-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="completed" value="0">
                        <input type="checkbox" name="completed" value="1" class="form-check-input me-2" style="vertical-align:middle;" {{ $todo->completed ? 'checked' : '' }}>
                    </form>
                    <span class="todo-title {{ $todo->completed ? 'completed' : '' }}">{{ $todo->title }}</span>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-outline-secondary todo-edit-btn" title="Edit" aria-label="Edit todo"
                    data-id="{{ $todo->id }}"
                    data-title="{{ e($todo->title) }}"
                    data-completed="{{ $todo->completed ? 1 : 0 }}"
                    data-action="{{ route('todos.update', $todo) }}">
                    {{-- pencil icon (inline SVG) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                      <path d="M12.146.146a.5.5 0 0 1 .708 0l2.0 2a.5.5 0 0 1 0 .708l-9.793 9.793-2.5.5a.5.5 0 0 1-.606-.606l.5-2.5L12.146.146zM11.207 2L3 10.207V13h2.793L14 4.793 11.207 2z"/>
                    </svg>
                  </button>

                  <form method="POST" action="{{ route('todos.destroy', $todo) }}" style="display:inline" class="todo-delete-form">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete todo">
                      {{-- trash icon (inline SVG) --}}
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 5h4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5v-7zM14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 1 1 0-2H5l1-1h4l1 1h3.5a1 1 0 0 1 1 1z"/>
                      </svg>
                    </button>
                  </form>
                </div>
            </li>
        @empty
            <li class="list-group-item">No todos yet.</li>
        @endforelse
    </ul>
</div>

<!-- Edit Todo Modal -->
<div class="modal fade" id="todoEditModal" tabindex="-1" aria-labelledby="todoEditModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="todoEditModalLabel">Edit Todo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="todo-edit-form">
          @csrf
          <input type="hidden" name="_method" value="PUT">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="todo-edit-title" class="form-control" required>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="todo-edit-completed" name="completed">
            <label class="form-check-label" for="todo-edit-completed">Completed</label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="todo-edit-save">Save changes</button>
      </div>
    </div>
  </div>
</div>
</div>
@endsection
