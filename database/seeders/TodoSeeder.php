<?php

namespace Database\Seeders;

use App\Models\Todo;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    public function run()
    {
        Todo::factory()->count(8)->create();

        // Add one explicit example
        Todo::create(['title' => 'Welcome — try editing or deleting this', 'completed' => false]);
    }
}
