<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\IssueCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\IssueCategoryController
 */
final class IssueCategoryControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $issueCategories = IssueCategory::factory()->count(3)->create();

        $response = $this->get(route('issue-categories.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\IssueCategoryController::class,
            'store',
            \App\Http\Requests\IssueCategoryStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = fake()->name();
        $type = fake()->word();

        $response = $this->post(route('issue-categories.store'), [
            'name' => $name,
            'type' => $type,
        ]);

        $issueCategories = IssueCategory::query()
            ->where('name', $name)
            ->where('type', $type)
            ->get();
        $this->assertCount(1, $issueCategories);
        $issueCategory = $issueCategories->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $issueCategory = IssueCategory::factory()->create();

        $response = $this->get(route('issue-categories.show', $issueCategory));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\IssueCategoryController::class,
            'update',
            \App\Http\Requests\IssueCategoryUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $issueCategory = IssueCategory::factory()->create();
        $name = fake()->name();
        $type = fake()->word();

        $response = $this->put(route('issue-categories.update', $issueCategory), [
            'name' => $name,
            'type' => $type,
        ]);

        $issueCategory->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $issueCategory->name);
        $this->assertEquals($type, $issueCategory->type);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $issueCategory = IssueCategory::factory()->create();

        $response = $this->delete(route('issue-categories.destroy', $issueCategory));

        $response->assertNoContent();

        $this->assertModelMissing($issueCategory);
    }
}
