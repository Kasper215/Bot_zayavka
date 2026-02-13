<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Agent;
use App\Models\DeliveryTask;
use App\Models\Product;

class DeliveryTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeliveryTask::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->text(),
            'status' => fake()->randomElement(["pending","assigned","completed"]),
            'due_date' => fake()->date(),
            'agent_id' => Agent::factory(),
            'product_id' => Product::factory(),
            'belongsTo' => fake()->word(),
        ];
    }
}
