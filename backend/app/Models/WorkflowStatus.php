<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStatus extends Model
{
    protected $fillable = [
        'value', 'label', 'color', 'text_color', 'bg_color',
        'category', 'description', 'sort_order', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];
}