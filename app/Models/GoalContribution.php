<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['space_id', 'financial_goal_id', 'contributed_by', 'amount', 'contributed_at', 'notes'])]
class GoalContribution extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'contributed_at' => 'date'];
    }

    public function contributor()
    {
        return $this->belongsTo(User::class, 'contributed_by');
    }

    public function goal()
    {
        return $this->belongsTo(FinancialGoal::class, 'financial_goal_id');
    }
}
