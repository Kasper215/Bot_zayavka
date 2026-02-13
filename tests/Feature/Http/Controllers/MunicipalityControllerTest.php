<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Municipality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\MunicipalityController
 */
final class MunicipalityControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $municipalities = Municipality::factory()->count(3)->create();

        $response = $this->get(route('municipalities.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MunicipalityController::class,
            'store',
            \App\Http\Requests\MunicipalityStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = fake()->name();

        $response = $this->post(route('municipalities.store'), [
            'name' => $name,
        ]);

        $municipalities = Municipality::query()
            ->where('name', $name)
            ->get();
        $this->assertCount(1, $municipalities);
        $municipality = $municipalities->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $municipality = Municipality::factory()->create();

        $response = $this->get(route('municipalities.show', $municipality));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\MunicipalityController::class,
            'update',
            \App\Http\Requests\MunicipalityUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $municipality = Municipality::factory()->create();
        $name = fake()->name();

        $response = $this->put(route('municipalities.update', $municipality), [
            'name' => $name,
        ]);

        $municipality->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $municipality->name);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $municipality = Municipality::factory()->create();

        $response = $this->delete(route('municipalities.destroy', $municipality));

        $response->assertNoContent();

        $this->assertModelMissing($municipality);
    }
}
