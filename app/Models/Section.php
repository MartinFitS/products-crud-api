<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Section extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'sections';

    protected $fillable = [
        'code',
        'name',
        'slug',
        'is_system',
    ];

    protected $attributes = [
        'is_system' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
