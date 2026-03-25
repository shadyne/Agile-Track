<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpicItem extends Model
{
    protected $fillable = [
        'epic_id', 'parent_id', 'board_id', 'user_id', 'assignee_id',
        'sprint_id', 'kode', 'judul', 'deskripsi', 'label', 'labels',
        'type', 'status', 'priority', 'start_date', 'end_date', 'original_estimate'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'labels'     => 'array',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(EpicItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EpicItem::class, 'parent_id');
    }
}