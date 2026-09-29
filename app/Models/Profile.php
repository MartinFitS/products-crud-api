<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Profile extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'profiles';

    protected $fillable = [
        'code',
        'name',
        'sections',
    ];
}
