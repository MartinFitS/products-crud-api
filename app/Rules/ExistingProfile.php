<?php

namespace App\Rules;

use App\Models\Profile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\InvalidArgumentException;

class ExistingProfile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $profileId = new ObjectId((string) $value);
        } catch (InvalidArgumentException) {
            $fail('El perfil seleccionado no tiene un identificador válido.');

            return;
        }

        if (! Profile::where('_id', $profileId)->exists()) {
            $fail('El perfil seleccionado no existe.');
        }
    }
}
