<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Agent;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SaleController
 */
final class SaleControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $sales = Sale::factory()->count(3)->create();

        $response = $this->get(route('sales.index'));

        $response->assertOk();
        $response->assertViewIs('sale.index');
        $response->assertViewHas('sales');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('sales.create'));

        $response->assertOk();
        $response->assertViewIs('sale.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SaleController::class,
            'store',
            \App\Http\Requests\SaleStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $sale_date = Carbon::parse(fake()->date());
        $quantity = fake()->numberBetween(-10000, 10000);
        $total_price = fake()->randomFloat(/** decimal_attributes **/);
        $product = Product::factory()->create();
        $agent = Agent::factory()->create();
        $customer = Customer::factory()->create();
        $belongsTo = fake()->word();

        $response = $this->post(route('sales.store'), [
            'sale_date' => $sale_date->toDateString(),
            'quantity' => $quantity,
            'total_price' => $total_price,
            'product_id' => $product->id,
            'agent_id' => $agent->id,
            'customer_id' => $customer->id,
            'belongsTo' => $belongsTo,
        ]);

        $sales = Sale::query()
            ->where('sale_date', $sale_date)
            ->where('quantity', $quantity)
            ->where('total_price', $total_price)
            ->where('product_id', $product->id)
            ->where('agent_id', $agent->id)
            ->where('customer_id', $customer->id)
            ->where('belongsTo', $belongsTo)
            ->get();
        $this->assertCount(1, $sales);
        $sale = $sales->first();

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHas('sale.id', $sale->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $sale = Sale::factory()->create();

        $response = $this->get(route('sales.show', $sale));

        $response->assertOk();
        $response->assertViewIs('sale.show');
        $response->assertViewHas('sale');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $sale = Sale::factory()->create();

        $response = $this->get(route('sales.edit', $sale));

        $response->assertOk();
        $response->assertViewIs('sale.edit');
        $response->assertViewHas('sale');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SaleController::class,
            'update',
            \App\Http\Requests\SaleUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $sale = Sale::factory()->create();
        $sale_date = Carbon::parse(fake()->date());
        $quantity = fake()->numberBetween(-10000, 10000);
        $total_price = fake()->randomFloat(/** decimal_attributes **/);
        $product = Product::factory()->create();
        $agent = Agent::factory()->create();
        $customer = Customer::factory()->create();
        $belongsTo = fake()->word();

        $response = $this->put(route('sales.update', $sale), [
            'sale_date' => $sale_date->toDateString(),
            'quantity' => $quantity,
            'total_price' => $total_price,
            'product_id' => $product->id,
            'agent_id' => $agent->id,
            'customer_id' => $customer->id,
            'belongsTo' => $belongsTo,
        ]);

        $sale->refresh();

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHas('sale.id', $sale->id);

        $this->assertEquals($sale_date, $sale->sale_date);
        $this->assertEquals($quantity, $sale->quantity);
        $this->assertEquals($total_price, $sale->total_price);
        $this->assertEquals($product->id, $sale->product_id);
        $this->assertEquals($agent->id, $sale->agent_id);
        $this->assertEquals($customer->id, $sale->customer_id);
        $this->assertEquals($belongsTo, $sale->belongsTo);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $sale = Sale::factory()->create();

        $response = $this->delete(route('sales.destroy', $sale));

        $response->assertRedirect(route('sales.index'));

        $this->assertModelMissing($sale);
    }
}
