<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Municipality;
use App\Models\Report;
use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ReportController
 */
final class ReportControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $reports = Report::factory()->count(3)->create();

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ReportController::class,
            'store',
            \App\Http\Requests\ReportStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $report_type = fake()->word();
        $to_user = Users::factory()->create();
        $municipality = Municipality::factory()->create();
        $received_at = fake()->word();

        $response = $this->post(route('reports.store'), [
            'report_type' => $report_type,
            'to_user_id' => $to_user->id,
            'municipality_id' => $municipality->id,
            'received_at' => $received_at,
        ]);

        $reports = Report::query()
            ->where('report_type', $report_type)
            ->where('to_user_id', $to_user->id)
            ->where('municipality_id', $municipality->id)
            ->where('received_at', $received_at)
            ->get();
        $this->assertCount(1, $reports);
        $report = $reports->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $report = Report::factory()->create();

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ReportController::class,
            'update',
            \App\Http\Requests\ReportUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $report = Report::factory()->create();
        $report_type = fake()->word();
        $to_user = Users::factory()->create();
        $municipality = Municipality::factory()->create();
        $received_at = fake()->word();

        $response = $this->put(route('reports.update', $report), [
            'report_type' => $report_type,
            'to_user_id' => $to_user->id,
            'municipality_id' => $municipality->id,
            'received_at' => $received_at,
        ]);

        $report->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($report_type, $report->report_type);
        $this->assertEquals($to_user->id, $report->to_user_id);
        $this->assertEquals($municipality->id, $report->municipality_id);
        $this->assertEquals($received_at, $report->received_at);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $report = Report::factory()->create();

        $response = $this->delete(route('reports.destroy', $report));

        $response->assertNoContent();

        $this->assertModelMissing($report);
    }
}
