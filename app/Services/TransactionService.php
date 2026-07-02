<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function create(array $data, int $userId, int $spaceId): Transaction
    {
        return DB::transaction(function () use ($data, $userId, $spaceId) {
            $data['user_id'] = $userId;
            $data['created_by'] = $userId;
            $data['space_id'] = $spaceId;
            $this->validateAccess($data, $userId, $spaceId);
            $transaction = Transaction::create($data);
            $this->apply($transaction, 1);

            return $transaction;
        });
    }

    public function update(Transaction $transaction, array $data, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            $this->apply($transaction, -1);
            $this->validateAccess($data, $userId, $transaction->space_id);
            $transaction->update($data);
            $this->apply($transaction->refresh(), 1);

            return $transaction;
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->apply($transaction, -1);
            $transaction->delete();
        });
    }

    private function apply(Transaction $transaction, int $direction): void
    {
        if ($transaction->status !== 'paid') {
            return;
        }

        $source = Account::whereKey($transaction->account_id)->lockForUpdate()->firstOrFail();
        $amount = (float) $transaction->amount * $direction;

        match ($transaction->type) {
            'income' => $source->increment('current_balance', $amount),
            'expense' => $source->decrement('current_balance', $amount),
            'transfer' => $this->transfer($source, $transaction->destination_account_id, $amount),
        };
    }

    private function transfer(Account $source, int $destinationId, float $amount): void
    {
        $destination = Account::whereKey($destinationId)->lockForUpdate()->firstOrFail();
        $source->decrement('current_balance', $amount);
        $destination->increment('current_balance', $amount);
    }

    private function validateAccess(array $data, int $userId, int $spaceId): void
    {
        $accountIds = array_filter([$data['account_id'] ?? null, $data['destination_account_id'] ?? null]);
        if (Account::where('space_id', $spaceId)->where(fn ($query) => $query->where('visibility', 'shared')->orWhere('user_id', $userId))->whereIn('id', $accountIds)->count() !== count(array_unique($accountIds))) {
            throw ValidationException::withMessages(['account_id' => 'Akun tidak valid.']);
        }
        if (($data['type'] ?? null) === 'transfer' && ($data['account_id'] ?? null) === ($data['destination_account_id'] ?? null)) {
            throw ValidationException::withMessages(['destination_account_id' => 'Akun tujuan harus berbeda.']);
        }
        if (! empty($data['category_id']) && ! Category::where('space_id', $spaceId)->whereKey($data['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak valid.']);
        }
    }
}
