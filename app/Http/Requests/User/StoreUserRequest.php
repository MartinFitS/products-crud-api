<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Rules\ExistingProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }

        if ($this->has('profile_ids')) {
            $profileIds = is_array($this->input('profile_ids'))
                ? $this->input('profile_ids')
                : [$this->input('profile_ids')];
            $profileIds = array_values(array_filter(
                $profileIds,
                fn (mixed $profileId): bool => ! blank($profileId)
            ));

            if ($profileIds === []) {
                $this->request->remove('profile_ids');
            } else {
                $this->merge(['profile_ids' => $profileIds]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'not_regex:/[\r\n]/',
                'max:254',
                Rule::unique(User::class, 'email'),
            ],
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'profile_ids' => ['sometimes', 'array', 'max:20'],
            'profile_ids.*' => ['string', 'distinct', new ExistingProfile],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'phone.regex' => 'El teléfono debe incluir código de país y usar formato E.164.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'photo.required' => 'La foto de perfil es obligatoria.',
            'photo.image' => 'La foto debe ser una imagen válida.',
            'photo.mimes' => 'La foto debe ser JPEG, JPG, PNG o WEBP.',
            'profile_ids.array' => 'Los perfiles deben enviarse como una lista.',
            'profile_ids.*.distinct' => 'No se permiten perfiles duplicados.',
        ];
    }
}
