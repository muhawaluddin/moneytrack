<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyClosing extends Model
{
    protected $fillable = ['space_id', 'closed_by', 'month', 'snapshot', 'notes', 'closed_at'];
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
