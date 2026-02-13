<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleStoreRequest extends FormRequest
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
            'sale_date' => ['required', 'date'],
            'quantity' => ['required', 'integer'],
            'total_price' => ['required', 'numeric', 'between:-99999999.99,99999999.99'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'belongsTo' => ['required', 'string'],
        ];
    }
}
