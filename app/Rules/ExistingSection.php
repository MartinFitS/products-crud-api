<?php

namespace App\Rules;

use App\Models\Section;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistingSection implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $isConfiguredSection = in_array($value, config('sections.allowed', []), true);

        if (! $isConfiguredSection && ! Section::where('slug', $value)->exists()) {
            $fail('La sección seleccionada no existe.');
        }
    }
}
