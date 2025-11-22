@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Todo</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('todos.update', $todo) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $todo->title) }}" required>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" name="completed" id="completed" {{ $todo->completed ? 'checked' : '' }}>
            <label class="form-check-label" for="completed">Completed</label>
        </div>

        <button class="btn btn-primary">Save</button>
        <a href="{{ route('todos.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
