<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $fillable = [
        'space_id', 'user_id', 'nama', 'framework', 'member_emails'
    ];

    protected $casts = [
        'member_emails' => 'array',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}