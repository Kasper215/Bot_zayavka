<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Agent;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;

class SaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sale_date' => fake()->date(),
            'quantity' => fake()->numberBetween(-10000, 10000),
            'total_price' => fake()->randomFloat(2, 0, 99999999.99),
            'product_id' => Product::factory(),
            'agent_id' => Agent::factory(),
            'customer_id' => Customer::factory(),
            'belongsTo' => fake()->word(),
        ];
    }
}
