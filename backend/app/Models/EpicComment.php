<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpicComment extends Model
{
    protected $fillable = ['epic_id', 'user_id', 'isi'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }
}