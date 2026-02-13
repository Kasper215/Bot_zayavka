<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResultReportUpdateRequest extends FormRequest
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
            'report_id' => ['required', 'integer', 'exists:Reports,id'],
            'topic' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'actions' => ['nullable', 'json'],
            'result' => ['nullable', 'json'],
            'difficulties' => ['nullable', 'json'],
        ];
    }
}
