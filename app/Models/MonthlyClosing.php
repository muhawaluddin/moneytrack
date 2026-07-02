<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['space_id', 'closed_by', 'month', 'snapshot', 'notes', 'closed_at'])]
class MonthlyClosing extends Model
{
    protected function casts(): array
    {
        return ['month' => 'date', 'snapshot' => 'array', 'closed_at' => 'datetime'];
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
