<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomingReportUpdateRequest extends FormRequest
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
            'received_from' => ['nullable', 'string'],
            'problem_description' => ['required', 'string'],
            'help_formats' => ['nullable', 'json'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
