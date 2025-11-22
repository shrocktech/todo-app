@extends('layouts.app')

@section('content')
<div class="container">
    <h1>To-Do List</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('todos.create') }}" class="btn btn-primary mb-3">Create Todo</a>

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
                <div>
                    <button type="button" class="btn btn-sm btn-secondary todo-edit-btn"
                        data-id="{{ $todo->id }}"
                        data-title="{{ e($todo->title) }}"
                        data-completed="{{ $todo->completed ? 1 : 0 }}"
                        data-action="{{ route('todos.update', $todo) }}">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('todos.destroy', $todo) }}" style="display:inline" class="todo-delete-form">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
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
