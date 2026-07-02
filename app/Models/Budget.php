<?php

namespace App\Models;

use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'space_id', 'category_id', 'month', 'limit_amount'])]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['month' => 'date', 'limit_amount' => 'decimal:2'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
