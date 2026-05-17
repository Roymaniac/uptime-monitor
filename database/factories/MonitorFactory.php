<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Monitor::class;

    public function definition(): array
    {
        return [
            'url'                  => 'https://' . $this->faker->unique()->domainName(),
            'check_interval'       => $this->faker->numberBetween(1, 60),
            'threshold'            => $this->faker->numberBetween(1, 5),
            'status'               => $this->faker->randomElement(['pending', 'up', 'down']),
            'consecutive_failures' => 0,
            'last_checked_at'      => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'last_checked_at' => null]);
    }

    public function up(): static
    {
        return $this->state([
            'status'          => 'up',
            'last_checked_at' => now()->subMinutes(5),
        ]);
    }

    public function down(): static
    {
        return $this->state([
            'status'               => 'down',
            'consecutive_failures' => 3,
            'last_checked_at'      => now()->subMinutes(5),
        ]);
    }
}
