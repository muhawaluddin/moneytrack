<?php

namespace App\Services;

use App\Models\Space;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\MoneyTrackNotification;

class MoneyTrackNotifier
{
    public function __construct(private readonly FinancialHealthService $health) {}

    public function transactionChanged(Transaction $transaction, User $actor, string $action): void
    {
        $transaction->loadMissing(['space.members', 'account']);
        $space = $transaction->space;
        if (! $space || $space->type !== 'family' || $transaction->account?->visibility !== 'shared') {
            return;
        }

        $labels = ['created' => 'mencatat', 'updated' => 'memperbarui', 'deleted' => 'menghapus'];
        $types = ['income' => 'pemasukan', 'expense' => 'pengeluaran', 'transfer' => 'transfer'];
        foreach ($space->members->where('id', '!=', $actor->id) as $member) {
            if (! $member->wantsNotification('transactions')) {
                continue;
            }
            $member->notify(new MoneyTrackNotification([
                'kind' => 'transaction', 'severity' => 'info', 'space_id' => $space->id,
                'title' => 'Aktivitas keuangan keluarga',
                'message' => $actor->name.' '.$labels[$action].' '.$types[$transaction->type].' Rp '.number_format((float) $transaction->amount, 0, ',', '.').' pada '.$transaction->account->name.'.',
                'url' => $action === 'deleted' ? route('transactions.index') : route('transactions.index', ['highlight' => $transaction->id]),
            ]));
        }
    }

    public function financialDataChanged(Space $space, User $actor, string $action, string $subject, string $url): void
    {
        if ($space->type !== 'family') {
            return;
        }

        $space->loadMissing('members');
        foreach ($space->members->where('id', '!=', $actor->id) as $member) {
            if (! $member->wantsNotification('data_updates')) {
                continue;
            }
            $member->notify(new MoneyTrackNotification([
                'kind' => 'financial_data', 'severity' => 'info', 'space_id' => $space->id,
                'title' => 'Data keuangan diperbarui',
                'message' => $actor->name.' '.$action.' '.$subject.'.',
                'url' => $url,
            ]));
        }
    }

    public function financialAlerts(Space $space): void
    {
        $space->loadMissing('members');
        foreach ($space->members as $member) {
            $result = $this->health->evaluate($space, $member);
            foreach ($result['issues'] as $issue) {
                if (! $member->wantsNotification('financial_health')) {
                    continue;
                }
                if ($this->alreadySentToday($member, $space, $issue['key'])) {
                    continue;
                }
                $member->notify(new MoneyTrackNotification([
                    'kind' => 'financial_health', 'severity' => $issue['severity'], 'space_id' => $space->id,
                    'issue_key' => $issue['key'], 'title' => $issue['title'], 'message' => $issue['message'], 'url' => $issue['url'],
                ]));
            }
        }
    }

    private function alreadySentToday(User $user, Space $space, string $key): bool
    {
        return $user->notifications()->where('created_at', '>=', now()->startOfDay())->get()
            ->contains(fn ($notification) => ($notification->data['kind'] ?? null) === 'financial_health'
                && ($notification->data['space_id'] ?? null) === $space->id
                && ($notification->data['issue_key'] ?? null) === $key);
    }
}
