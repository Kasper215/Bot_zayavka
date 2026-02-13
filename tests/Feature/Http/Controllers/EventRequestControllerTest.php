<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\EventRequest;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\EventRequestController
 */
final class EventRequestControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $eventRequests = EventRequest::factory()->count(3)->create();

        $response = $this->get(route('event-requests.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\EventRequestController::class,
            'store',
            \App\Http\Requests\EventRequestStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $report = Report::factory()->create();
        $event_date = Carbon::parse(fake()->date());
        $description = fake()->text();
        $target_audience = fake()->word();
        $participants_count = fake()->numberBetween(-10000, 10000);

        $response = $this->post(route('event-requests.store'), [
            'report_id' => $report->id,
            'event_date' => $event_date->toDateString(),
            'description' => $description,
            'target_audience' => $target_audience,
            'participants_count' => $participants_count,
        ]);

        $eventRequests = EventRequest::query()
            ->where('report_id', $report->id)
            ->where('event_date', $event_date)
            ->where('description', $description)
            ->where('target_audience', $target_audience)
            ->where('participants_count', $participants_count)
            ->get();
        $this->assertCount(1, $eventRequests);
        $eventRequest = $eventRequests->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $eventRequest = EventRequest::factory()->create();

        $response = $this->get(route('event-requests.show', $eventRequest));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\EventRequestController::class,
            'update',
            \App\Http\Requests\EventRequestUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $eventRequest = EventRequest::factory()->create();
        $report = Report::factory()->create();
        $event_date = Carbon::parse(fake()->date());
        $description = fake()->text();
        $target_audience = fake()->word();
        $participants_count = fake()->numberBetween(-10000, 10000);

        $response = $this->put(route('event-requests.update', $eventRequest), [
            'report_id' => $report->id,
            'event_date' => $event_date->toDateString(),
            'description' => $description,
            'target_audience' => $target_audience,
            'participants_count' => $participants_count,
        ]);

        $eventRequest->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($report->id, $eventRequest->report_id);
        $this->assertEquals($event_date, $eventRequest->event_date);
        $this->assertEquals($description, $eventRequest->description);
        $this->assertEquals($target_audience, $eventRequest->target_audience);
        $this->assertEquals($participants_count, $eventRequest->participants_count);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $eventRequest = EventRequest::factory()->create();

        $response = $this->delete(route('event-requests.destroy', $eventRequest));

        $response->assertNoContent();

        $this->assertModelMissing($eventRequest);
    }
}
