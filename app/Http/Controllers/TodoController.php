<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index()
    {
        $todos = Todo::orderBy('created_at', 'desc')->get();
        return view('todos.index', compact('todos'));
    }

    public function create()
    {
        return view('todos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo = Todo::create($data + ['completed' => false]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'todo' => $todo]);
        }

        return redirect()->route('todos.index')->with('success', 'Todo created.');
    }

    public function edit(Todo $todo)
    {
        return view('todos.edit', compact('todo'));
    }

    public function update(Request $request, Todo $todo)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'completed' => 'sometimes|boolean',
        ]);

        $todo->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'completed' => $todo->completed]);
        }

        return redirect()->route('todos.index')->with('success', 'Todo updated.');
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('todos.index')->with('success', 'Todo deleted.');
    }
}
