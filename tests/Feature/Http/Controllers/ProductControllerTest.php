<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ProductController
 */
final class ProductControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $products = Product::factory()->count(3)->create();

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertViewIs('product.index');
        $response->assertViewHas('products');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('products.create'));

        $response->assertOk();
        $response->assertViewIs('product.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ProductController::class,
            'store',
            \App\Http\Requests\ProductStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $name = fake()->name();
        $description = fake()->text();
        $price = fake()->randomFloat(/** decimal_attributes **/);
        $count = fake()->numberBetween(-10000, 10000);
        $supplier = Supplier::factory()->create();
        $product_category = ProductCategory::factory()->create();
        $belongsTo = fake()->word();

        $response = $this->post(route('products.store'), [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'count' => $count,
            'supplier_id' => $supplier->id,
            'product_category_id' => $product_category->id,
            'belongsTo' => $belongsTo,
        ]);

        $products = Product::query()
            ->where('name', $name)
            ->where('description', $description)
            ->where('price', $price)
            ->where('count', $count)
            ->where('supplier_id', $supplier->id)
            ->where('product_category_id', $product_category->id)
            ->where('belongsTo', $belongsTo)
            ->get();
        $this->assertCount(1, $products);
        $product = $products->first();

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('product.id', $product->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertViewIs('product.show');
        $response->assertViewHas('product');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.edit', $product));

        $response->assertOk();
        $response->assertViewIs('product.edit');
        $response->assertViewHas('product');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ProductController::class,
            'update',
            \App\Http\Requests\ProductUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $product = Product::factory()->create();
        $name = fake()->name();
        $description = fake()->text();
        $price = fake()->randomFloat(/** decimal_attributes **/);
        $count = fake()->numberBetween(-10000, 10000);
        $supplier = Supplier::factory()->create();
        $product_category = ProductCategory::factory()->create();
        $belongsTo = fake()->word();

        $response = $this->put(route('products.update', $product), [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'count' => $count,
            'supplier_id' => $supplier->id,
            'product_category_id' => $product_category->id,
            'belongsTo' => $belongsTo,
        ]);

        $product->refresh();

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('product.id', $product->id);

        $this->assertEquals($name, $product->name);
        $this->assertEquals($description, $product->description);
        $this->assertEquals($price, $product->price);
        $this->assertEquals($count, $product->count);
        $this->assertEquals($supplier->id, $product->supplier_id);
        $this->assertEquals($product_category->id, $product->product_category_id);
        $this->assertEquals($belongsTo, $product->belongsTo);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $product = Product::factory()->create();

        $response = $this->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));

        $this->assertModelMissing($product);
    }
}
