<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Report;
use App\Models\ResultReport;

class ResultReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ResultReport::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'topic' => fake()->word(),
            'description' => fake()->text(),
            'actions' => '{}',
            'result' => '{}',
            'difficulties' => '{}',
        ];
    }
}
