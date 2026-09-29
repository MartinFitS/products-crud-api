<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'products';

    protected $fillable = ['code', 'name', 'brand', 'price', 'photo'];

    protected function casts(): array
    {
        return ['price' => 'float'];
    }
}
