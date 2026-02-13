<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\IncomingReport;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\IncomingReportController
 */
final class IncomingReportControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $incomingReports = IncomingReport::factory()->count(3)->create();

        $response = $this->get(route('incoming-reports.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\IncomingReportController::class,
            'store',
            \App\Http\Requests\IncomingReportStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $report = Report::factory()->create();
        $problem_description = fake()->text();

        $response = $this->post(route('incoming-reports.store'), [
            'report_id' => $report->id,
            'problem_description' => $problem_description,
        ]);

        $incomingReports = IncomingReport::query()
            ->where('report_id', $report->id)
            ->where('problem_description', $problem_description)
            ->get();
        $this->assertCount(1, $incomingReports);
        $incomingReport = $incomingReports->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $incomingReport = IncomingReport::factory()->create();

        $response = $this->get(route('incoming-reports.show', $incomingReport));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\IncomingReportController::class,
            'update',
            \App\Http\Requests\IncomingReportUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $incomingReport = IncomingReport::factory()->create();
        $report = Report::factory()->create();
        $problem_description = fake()->text();

        $response = $this->put(route('incoming-reports.update', $incomingReport), [
            'report_id' => $report->id,
            'problem_description' => $problem_description,
        ]);

        $incomingReport->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($report->id, $incomingReport->report_id);
        $this->assertEquals($problem_description, $incomingReport->problem_description);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $incomingReport = IncomingReport::factory()->create();

        $response = $this->delete(route('incoming-reports.destroy', $incomingReport));

        $response->assertNoContent();

        $this->assertModelMissing($incomingReport);
    }
}
