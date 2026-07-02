<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialGoal extends Model
{
    protected $fillable = ['space_id', 'created_by', 'name', 'target_amount', 'current_amount', 'deadline', 'color', 'status', 'description'];
    protected function casts(): array
    {
        return ['target_amount' => 'decimal:2', 'current_amount' => 'decimal:2', 'deadline' => 'date'];
    }

    public function contributions()
    {
        return $this->hasMany(GoalContribution::class)->latest('contributed_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
