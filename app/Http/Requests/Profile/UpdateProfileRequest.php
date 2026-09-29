<?php

namespace App\Http\Requests\Profile;

use App\Models\Profile;
use App\Rules\ExistingSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('sections'))) {
            $this->merge([
                'sections' => array_values(array_unique($this->input('sections'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique(Profile::class, 'name')->ignore($this->route('id'), '_id'),
            ],
            'sections' => ['sometimes', 'required', 'array', 'min:1', 'max:20'],
            'sections.*' => ['required', 'string', 'distinct', new ExistingSection],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un perfil con este nombre.',
            'sections.min' => 'Selecciona al menos una sección.',
        ];
    }
}
