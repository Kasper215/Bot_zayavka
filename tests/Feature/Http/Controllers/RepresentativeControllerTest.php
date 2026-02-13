<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Representative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\RepresentativeController
 */
final class RepresentativeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $representatives = Representative::factory()->count(3)->create();

        $response = $this->get(route('representatives.index'));

        $response->assertOk();
        $response->assertViewIs('representative.index');
        $response->assertViewHas('representatives');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('representatives.create'));

        $response->assertOk();
        $response->assertViewIs('representative.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RepresentativeController::class,
            'store',
            \App\Http\Requests\AgentStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $response = $this->post(route('representatives.store'));

        $response->assertRedirect(route('representatives.index'));
        $response->assertSessionHas('representative.id', $representative->id);

        $this->assertDatabaseHas(representatives, [ /* ... */ ]);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $representative = Representative::factory()->create();

        $response = $this->get(route('representatives.show', $representative));

        $response->assertOk();
        $response->assertViewIs('representative.show');
        $response->assertViewHas('representative');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $representative = Representative::factory()->create();

        $response = $this->get(route('representatives.edit', $representative));

        $response->assertOk();
        $response->assertViewIs('representative.edit');
        $response->assertViewHas('representative');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\RepresentativeController::class,
            'update',
            \App\Http\Requests\AgentUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $representative = Representative::factory()->create();

        $response = $this->put(route('representatives.update', $representative));

        $representative->refresh();

        $response->assertRedirect(route('representatives.index'));
        $response->assertSessionHas('representative.id', $representative->id);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $representative = Representative::factory()->create();

        $response = $this->delete(route('representatives.destroy', $representative));

        $response->assertRedirect(route('representatives.index'));

        $this->assertModelMissing($representative);
    }
}
