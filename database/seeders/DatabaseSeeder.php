<?php

namespace Database\Seeders;

// use App\Models\User;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Optionally, you can also seed some monitors for testing
        Monitor::factory(5)->create();
        MonitorCheck::factory(20)->create();
    }
}
