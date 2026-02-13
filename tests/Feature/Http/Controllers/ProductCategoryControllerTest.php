<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ProductCategoryController
 */
final class ProductCategoryControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $productCategories = ProductCategory::factory()->count(3)->create();

        $response = $this->get(route('product-categories.index'));

        $response->assertOk();
        $response->assertViewIs('productCategory.index');
        $response->assertViewHas('productCategories');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('product-categories.create'));

        $response->assertOk();
        $response->assertViewIs('productCategory.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ProductCategoryController::class,
            'store',
            \App\Http\Requests\ProductCategoryStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $name = fake()->name();
        $description = fake()->text();
        $hasMany = fake()->word();

        $response = $this->post(route('product-categories.store'), [
            'name' => $name,
            'description' => $description,
            'hasMany' => $hasMany,
        ]);

        $productCategories = ProductCategory::query()
            ->where('name', $name)
            ->where('description', $description)
            ->where('hasMany', $hasMany)
            ->get();
        $this->assertCount(1, $productCategories);
        $productCategory = $productCategories->first();

        $response->assertRedirect(route('productCategories.index'));
        $response->assertSessionHas('productCategory.id', $productCategory->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $productCategory = ProductCategory::factory()->create();

        $response = $this->get(route('product-categories.show', $productCategory));

        $response->assertOk();
        $response->assertViewIs('productCategory.show');
        $response->assertViewHas('productCategory');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $productCategory = ProductCategory::factory()->create();

        $response = $this->get(route('product-categories.edit', $productCategory));

        $response->assertOk();
        $response->assertViewIs('productCategory.edit');
        $response->assertViewHas('productCategory');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ProductCategoryController::class,
            'update',
            \App\Http\Requests\ProductCategoryUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $productCategory = ProductCategory::factory()->create();
        $name = fake()->name();
        $description = fake()->text();
        $hasMany = fake()->word();

        $response = $this->put(route('product-categories.update', $productCategory), [
            'name' => $name,
            'description' => $description,
            'hasMany' => $hasMany,
        ]);

        $productCategory->refresh();

        $response->assertRedirect(route('productCategories.index'));
        $response->assertSessionHas('productCategory.id', $productCategory->id);

        $this->assertEquals($name, $productCategory->name);
        $this->assertEquals($description, $productCategory->description);
        $this->assertEquals($hasMany, $productCategory->hasMany);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $productCategory = ProductCategory::factory()->create();

        $response = $this->delete(route('product-categories.destroy', $productCategory));

        $response->assertRedirect(route('productCategories.index'));

        $this->assertModelMissing($productCategory);
    }
}
