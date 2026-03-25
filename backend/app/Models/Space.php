<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Space extends Model
{
    protected $fillable = ['user_id', 'nama', 'key', 'deskripsi', 'member_emails'];

    protected $casts = [
        'member_emails' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'space_id');
    }
    public function boards(): HasMany
{
    return $this->hasMany(Board::class);
}
}