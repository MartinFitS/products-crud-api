<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
