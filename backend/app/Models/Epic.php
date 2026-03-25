<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epic extends Model
{
    protected $fillable = [
        'board_id', 'user_id', 'assignee_id', 'kode',
        'judul', 'deskripsi', 'labels', 'type',
        'status', 'priority', 'start_date', 'end_date'
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EpicItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EpicAttachment::class);
    }
    public function comments(): HasMany
    {
        return $this->hasMany(EpicComment::class)->orderBy('created_at', 'asc');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->orderBy('created_at', 'desc');
    }
}