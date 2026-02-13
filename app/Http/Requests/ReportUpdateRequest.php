<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportUpdateRequest extends FormRequest
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
            'report_type' => ['required', 'string'],
            'from_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'municipality_id' => ['required', 'integer', 'exists:Municipalities,id'],
            'received_at' => ['required', 'string'],
            'documents' => ['nullable', 'json'],
        ];
    }
}
