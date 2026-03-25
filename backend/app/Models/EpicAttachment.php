<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpicAttachment extends Model
{
    protected $fillable = ['epic_id', 'nama_file', 'path', 'mime_type', 'ukuran'];

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }
}