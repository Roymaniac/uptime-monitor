<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorCheck>
 */
class MonitorCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = MonitorCheck::class;

    public function definition(): array
    {
        $isUp       = $this->faker->boolean(80);
        $statusCode = $isUp
            ? $this->faker->randomElement([200, 200, 200, 301, 302])
            : $this->faker->randomElement([0, 500, 503, 404]);

        return [
            'monitor_id'       => Monitor::factory(),
            'status_code'      => $statusCode,
            'response_time_ms' => $statusCode === 0 ? null : $this->faker->numberBetween(50, 2000),
            'is_up'            => $isUp,
            'checked_at'       => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
