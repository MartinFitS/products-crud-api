<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'brand' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'between:0,999.99'],
            'photo' => ['sometimes', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'brand.required' => 'La marca es obligatoria.',
            'price.required' => 'El precio es obligatorio.',
            'price.decimal' => 'El precio debe tener máximo dos decimales.',
            'price.between' => 'El precio debe estar entre 0 y 999.99.',
            'photo.image' => 'La foto debe ser una imagen válida.',
            'photo.mimes' => 'La foto debe ser JPEG, JPG, PNG o WEBP.',
        ];
    }
}
