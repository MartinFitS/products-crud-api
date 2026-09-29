<?php

namespace App\Http\Requests\AuditLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'action' => ['sometimes', 'nullable', Rule::in(['created', 'updated', 'status_updated', 'deleted'])],
            'auditable_type' => ['sometimes', 'nullable', Rule::in(['product', 'user', 'profile'])],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}
