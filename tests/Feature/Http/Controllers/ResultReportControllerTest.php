<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Report;
use App\Models\ResultReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ResultReportController
 */
final class ResultReportControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $resultReports = ResultReport::factory()->count(3)->create();

        $response = $this->get(route('result-reports.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ResultReportController::class,
            'store',
            \App\Http\Requests\ResultReportStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $report = Report::factory()->create();
        $topic = fake()->word();

        $response = $this->post(route('result-reports.store'), [
            'report_id' => $report->id,
            'topic' => $topic,
        ]);

        $resultReports = ResultReport::query()
            ->where('report_id', $report->id)
            ->where('topic', $topic)
            ->get();
        $this->assertCount(1, $resultReports);
        $resultReport = $resultReports->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $resultReport = ResultReport::factory()->create();

        $response = $this->get(route('result-reports.show', $resultReport));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ResultReportController::class,
            'update',
            \App\Http\Requests\ResultReportUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $resultReport = ResultReport::factory()->create();
        $report = Report::factory()->create();
        $topic = fake()->word();

        $response = $this->put(route('result-reports.update', $resultReport), [
            'report_id' => $report->id,
            'topic' => $topic,
        ]);

        $resultReport->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($report->id, $resultReport->report_id);
        $this->assertEquals($topic, $resultReport->topic);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $resultReport = ResultReport::factory()->create();

        $response = $this->delete(route('result-reports.destroy', $resultReport));

        $response->assertNoContent();

        $this->assertModelMissing($resultReport);
    }
}
