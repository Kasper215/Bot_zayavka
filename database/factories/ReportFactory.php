<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Municipality;
use App\Models\Report;
use App\Models\Users;

class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'report_type' => fake()->word(),
            'from_user_id' => Users::factory(),
            'to_user_id' => Users::factory(),
            'municipality_id' => Municipality::factory(),
            'received_at' => fake()->word(),
            'documents' => '{}',
        ];
    }
}
