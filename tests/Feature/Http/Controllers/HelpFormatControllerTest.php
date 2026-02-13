<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\HelpFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\HelpFormatController
 */
final class HelpFormatControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $helpFormats = HelpFormat::factory()->count(3)->create();

        $response = $this->get(route('help-formats.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\HelpFormatController::class,
            'store',
            \App\Http\Requests\HelpFormatStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = fake()->name();

        $response = $this->post(route('help-formats.store'), [
            'name' => $name,
        ]);

        $helpFormats = HelpFormat::query()
            ->where('name', $name)
            ->get();
        $this->assertCount(1, $helpFormats);
        $helpFormat = $helpFormats->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $helpFormat = HelpFormat::factory()->create();

        $response = $this->get(route('help-formats.show', $helpFormat));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\HelpFormatController::class,
            'update',
            \App\Http\Requests\HelpFormatUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $helpFormat = HelpFormat::factory()->create();
        $name = fake()->name();

        $response = $this->put(route('help-formats.update', $helpFormat), [
            'name' => $name,
        ]);

        $helpFormat->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $helpFormat->name);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $helpFormat = HelpFormat::factory()->create();

        $response = $this->delete(route('help-formats.destroy', $helpFormat));

        $response->assertNoContent();

        $this->assertModelMissing($helpFormat);
    }
}
