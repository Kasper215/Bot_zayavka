<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\IncomingReport;
use App\Models\Report;

class IncomingReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IncomingReport::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'received_from' => fake()->word(),
            'problem_description' => fake()->text(),
            'help_formats' => '{}',
            'comment' => fake()->text(),
        ];
    }
}
