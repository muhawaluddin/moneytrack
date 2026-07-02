<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'space_id', 'created_by', 'account_id', 'destination_account_id', 'category_id', 'type', 'amount', 'transacted_at', 'description', 'receipt_path', 'status', 'is_recurring', 'recurring_rule'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transacted_at' => 'datetime', 'is_recurring' => 'boolean'];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function destinationAccount()
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
