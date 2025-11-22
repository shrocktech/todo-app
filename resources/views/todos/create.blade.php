@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Todo</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('todos.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
        </div>
        <button class="btn btn-primary">Create</button>
        <a href="{{ route('todos.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
