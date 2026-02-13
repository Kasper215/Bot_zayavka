<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'between:-999999.99,999999.99'],
            'count' => ['required', 'integer'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'belongsTo' => ['required', 'string'],
        ];
    }
}
