<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\EventRequest;
use App\Models\Report;

class EventRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'event_date' => fake()->date(),
            'description' => fake()->text(),
            'target_audience' => fake()->word(),
            'participants_count' => fake()->numberBetween(-10000, 10000),
            'help_formats' => '{}',
            'comment' => fake()->text(),
        ];
    }
}
