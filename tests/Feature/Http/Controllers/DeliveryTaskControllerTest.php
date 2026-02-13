<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Agent;
use App\Models\DeliveryTask;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DeliveryTaskController
 */
final class DeliveryTaskControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_displays_view(): void
    {
        $deliveryTasks = DeliveryTask::factory()->count(3)->create();

        $response = $this->get(route('delivery-tasks.index'));

        $response->assertOk();
        $response->assertViewIs('deliveryTask.index');
        $response->assertViewHas('deliveryTasks');
    }


    #[Test]
    public function create_displays_view(): void
    {
        $response = $this->get(route('delivery-tasks.create'));

        $response->assertOk();
        $response->assertViewIs('deliveryTask.create');
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DeliveryTaskController::class,
            'store',
            \App\Http\Requests\DeliveryTaskStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_redirects(): void
    {
        $title = fake()->sentence(4);
        $description = fake()->text();
        $status = fake()->randomElement(/** enum_attributes **/);
        $due_date = Carbon::parse(fake()->date());
        $agent = Agent::factory()->create();
        $product = Product::factory()->create();
        $belongsTo = fake()->word();

        $response = $this->post(route('delivery-tasks.store'), [
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'due_date' => $due_date->toDateString(),
            'agent_id' => $agent->id,
            'product_id' => $product->id,
            'belongsTo' => $belongsTo,
        ]);

        $deliveryTasks = DeliveryTask::query()
            ->where('title', $title)
            ->where('description', $description)
            ->where('status', $status)
            ->where('due_date', $due_date)
            ->where('agent_id', $agent->id)
            ->where('product_id', $product->id)
            ->where('belongsTo', $belongsTo)
            ->get();
        $this->assertCount(1, $deliveryTasks);
        $deliveryTask = $deliveryTasks->first();

        $response->assertRedirect(route('deliveryTasks.index'));
        $response->assertSessionHas('deliveryTask.id', $deliveryTask->id);
    }


    #[Test]
    public function show_displays_view(): void
    {
        $deliveryTask = DeliveryTask::factory()->create();

        $response = $this->get(route('delivery-tasks.show', $deliveryTask));

        $response->assertOk();
        $response->assertViewIs('deliveryTask.show');
        $response->assertViewHas('deliveryTask');
    }


    #[Test]
    public function edit_displays_view(): void
    {
        $deliveryTask = DeliveryTask::factory()->create();

        $response = $this->get(route('delivery-tasks.edit', $deliveryTask));

        $response->assertOk();
        $response->assertViewIs('deliveryTask.edit');
        $response->assertViewHas('deliveryTask');
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\DeliveryTaskController::class,
            'update',
            \App\Http\Requests\DeliveryTaskUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $deliveryTask = DeliveryTask::factory()->create();
        $title = fake()->sentence(4);
        $description = fake()->text();
        $status = fake()->randomElement(/** enum_attributes **/);
        $due_date = Carbon::parse(fake()->date());
        $agent = Agent::factory()->create();
        $product = Product::factory()->create();
        $belongsTo = fake()->word();

        $response = $this->put(route('delivery-tasks.update', $deliveryTask), [
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'due_date' => $due_date->toDateString(),
            'agent_id' => $agent->id,
            'product_id' => $product->id,
            'belongsTo' => $belongsTo,
        ]);

        $deliveryTask->refresh();

        $response->assertRedirect(route('deliveryTasks.index'));
        $response->assertSessionHas('deliveryTask.id', $deliveryTask->id);

        $this->assertEquals($title, $deliveryTask->title);
        $this->assertEquals($description, $deliveryTask->description);
        $this->assertEquals($status, $deliveryTask->status);
        $this->assertEquals($due_date, $deliveryTask->due_date);
        $this->assertEquals($agent->id, $deliveryTask->agent_id);
        $this->assertEquals($product->id, $deliveryTask->product_id);
        $this->assertEquals($belongsTo, $deliveryTask->belongsTo);
    }


    #[Test]
    public function destroy_deletes_and_redirects(): void
    {
        $deliveryTask = DeliveryTask::factory()->create();

        $response = $this->delete(route('delivery-tasks.destroy', $deliveryTask));

        $response->assertRedirect(route('deliveryTasks.index'));

        $this->assertModelMissing($deliveryTask);
    }
}
