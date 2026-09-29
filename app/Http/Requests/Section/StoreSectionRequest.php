<?php

namespace App\Http\Requests\Section;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Section::class, 'name')],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(Section::class, 'slug'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una sección con este nombre.',
            'slug.unique' => 'Ya existe una sección con este identificador.',
            'slug.regex' => 'El identificador solo puede contener letras minúsculas, números y guiones.',
        ];
    }
}
