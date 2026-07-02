<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\MonthlyClosing;
use App\Models\Space;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class MonthlyClosingService
{
    public function close(Space $space, User $user, CarbonInterface $month, ?string $notes): MonthlyClosing
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $accountIds = $space->visibleAccounts($user)->pluck('id');
        $totals = $space->transactions()->whereIn('account_id', $accountIds)->where('status', 'paid')->whereBetween('transacted_at', [$start, $end])->selectRaw('type,SUM(amount) total')->groupBy('type')->pluck('total', 'type');
        $accounts = $space->visibleAccounts($user)->get();
        $accountSnapshots = $accounts->map(function ($account) use ($space, $end) {
            $source = $space->transactions()->where('account_id', $account->id)->where('status', 'paid')->where('transacted_at', '<=', $end);
            $income = (float) (clone $source)->where('type', 'income')->sum('amount');
            $expense = (float) (clone $source)->where('type', 'expense')->sum('amount');
            $outgoing = (float) (clone $source)->where('type', 'transfer')->sum('amount');
            $incoming = (float) $space->transactions()->where('destination_account_id', $account->id)->where('status', 'paid')->where('type', 'transfer')->where('transacted_at', '<=', $end)->sum('amount');

            return ['id' => $account->id, 'name' => $account->name, 'balance' => (float) $account->opening_balance + $income - $expense - $outgoing + $incoming];
        });
        $snapshot = [
            'income' => (float) ($totals['income'] ?? 0),
            'expense' => (float) ($totals['expense'] ?? 0),
            'balance' => (float) $accountSnapshots->sum('balance'),
            'accounts' => $accountSnapshots->toArray(),
            'budgets' => Budget::where('space_id', $space->id)->whereDate('month', $start)->count(),
        ];

        return MonthlyClosing::create(['space_id' => $space->id, 'closed_by' => $user->id, 'month' => $start, 'snapshot' => $snapshot, 'notes' => $notes, 'closed_at' => now()]);
    }

    public function assertOpen(int $spaceId, CarbonInterface|string $date, string $field = 'transacted_at'): void
    {
        $month = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        if (MonthlyClosing::where('space_id', $spaceId)->whereDate('month', $month->copy()->startOfMonth())->exists()) {
            throw ValidationException::withMessages([$field => 'Periode '.$month->translatedFormat('F Y').' sudah ditutup. Buka kembali buku bulanan sebelum mengubah data.']);
        }
    }
}
