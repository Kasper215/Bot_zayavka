<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryTaskUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:pending,assigned,completed'],
            'due_date' => ['required', 'date'],
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'belongsTo' => ['required', 'string'],
        ];
    }
}
