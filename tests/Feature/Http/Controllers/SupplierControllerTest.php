<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SupplierController
 */
final class SupplierControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $suppliers = Supplier::factory()->count(3)->create();

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertViewIs('supplier.index');
        $response->assertViewHas('suppliers');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('suppliers.create'));

        $response->assertOk();
        $response->assertViewIs('supplier.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SupplierController::class,
            'store',
            \App\Http\Requests\SupplierStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $name = fake()->name();
        $phone = fake()->phoneNumber();
        $hasMany = fake()->word();

        $response = $this->post(route('suppliers.store'), [
            'name' => $name,
            'phone' => $phone,
            'hasMany' => $hasMany,
        ]);

        $suppliers = Supplier::query()
            ->where('name', $name)
            ->where('phone', $phone)
            ->where('hasMany', $hasMany)
            ->get();
        $this->assertCount(1, $suppliers);
        $supplier = $suppliers->first();

        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('supplier.id', $supplier->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertViewIs('supplier.show');
        $response->assertViewHas('supplier');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->get(route('suppliers.edit', $supplier));

        $response->assertOk();
        $response->assertViewIs('supplier.edit');
        $response->assertViewHas('supplier');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SupplierController::class,
            'update',
            \App\Http\Requests\SupplierUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $supplier = Supplier::factory()->create();
        $name = fake()->name();
        $phone = fake()->phoneNumber();
        $hasMany = fake()->word();

        $response = $this->put(route('suppliers.update', $supplier), [
            'name' => $name,
            'phone' => $phone,
            'hasMany' => $hasMany,
        ]);

        $supplier->refresh();

        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHas('supplier.id', $supplier->id);

        $this->assertEquals($name, $supplier->name);
        $this->assertEquals($phone, $supplier->phone);
        $this->assertEquals($hasMany, $supplier->hasMany);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('suppliers.destroy', $supplier));

        $response->assertRedirect(route('suppliers.index'));

        $this->assertModelMissing($supplier);
    }
}
