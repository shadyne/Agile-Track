<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpicItem extends Model
{
   public function board()
{
    return $this->belongsTo(Board::class);
}
}
