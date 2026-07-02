<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'space_id', 'visibility', 'name', 'type', 'bank_name', 'account_number', 'opening_balance', 'current_balance', 'currency', 'color', 'icon', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2', 'is_active' => 'boolean', 'account_number' => 'encrypted'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomingTransfers()
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
