<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Label extends Model
{
    protected $fillable = ['board_id', 'nama'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}