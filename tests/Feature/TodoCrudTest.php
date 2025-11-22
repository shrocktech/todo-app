<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Todo;

class TodoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_todo()
    {
        $response = $this->post(route('todos.store'), ['title' => 'Test todo']);
        $response->assertRedirect(route('todos.index'));
        $this->assertDatabaseHas('todos', ['title' => 'Test todo']);
    }

    public function test_can_update_todo()
    {
        $todo = Todo::factory()->create(['title' => 'Old title', 'completed' => false]);
        $response = $this->put(route('todos.update', $todo), ['title' => 'New title', 'completed' => 1]);
        $response->assertRedirect(route('todos.index'));
        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'New title', 'completed' => 1]);
    }

    public function test_can_delete_todo()
    {
        $todo = Todo::factory()->create();
        $response = $this->delete(route('todos.destroy', $todo));
        $response->assertRedirect(route('todos.index'));
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_ajax_update_returns_json()
    {
        $todo = Todo::factory()->create(['completed' => false]);
        $response = $this->post(route('todos.update', $todo), ['completed' => 1, '_method' => 'PUT'], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'completed' => 1]);
    }
}
