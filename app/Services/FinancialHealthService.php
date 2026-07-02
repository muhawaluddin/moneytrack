<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Space;
use App\Models\User;

class FinancialHealthService
{
    public function evaluate(Space $space, User $user): array
    {
        $start = now()->startOfMonth();
        $accountIds = $space->visibleAccounts($user)->where('is_active', true)->pluck('id');
        $transactions = $space->transactions()->whereIn('account_id', $accountIds)->where('status', 'paid');
        $totals = (clone $transactions)->whereBetween('transacted_at', [$start, now()->endOfMonth()])
            ->selectRaw('type, SUM(amount) total')->groupBy('type')->pluck('total', 'type');
        $income = (float) ($totals['income'] ?? 0);
        $expense = (float) ($totals['expense'] ?? 0);
        $balance = (float) $space->visibleAccounts($user)->where('is_active', true)->sum('current_balance');
        $issues = collect();

        if ($balance < 0) {
            $issues->push([
                'key' => 'negative_balance', 'severity' => 'critical',
                'title' => 'Saldo likuid negatif',
                'message' => 'Total saldo yang dapat Anda lihat berada di bawah nol.',
                'url' => route('accounts.index'),
            ]);
        }

        if ($expense > 0 && $expense > $income) {
            $ratio = $income > 0 ? $expense / $income : null;
            $issues->push([
                'key' => 'negative_cashflow',
                'severity' => $ratio === null || $ratio >= 1.25 ? 'critical' : 'warning',
                'title' => 'Pengeluaran melebihi pemasukan',
                'message' => $income > 0
                    ? 'Pengeluaran bulan ini mencapai '.round($ratio * 100).'% dari pemasukan.'
                    : 'Ada pengeluaran bulan ini, tetapi belum ada pemasukan tercatat.',
                'url' => route('reports.index'),
            ]);
        }

        $budgets = Budget::where('space_id', $space->id)->with('category')->whereDate('month', $start)->get();
        foreach ($budgets as $budget) {
            $spent = (float) (clone $transactions)->where('type', 'expense')->where('category_id', $budget->category_id)
                ->whereBetween('transacted_at', [$start, now()->endOfMonth()])->sum('amount');
            $percentage = $budget->limit_amount > 0 ? $spent / (float) $budget->limit_amount * 100 : 0;
            if ($percentage >= 80) {
                $issues->push([
                    'key' => 'budget_'.$budget->id.'_'.($percentage >= 100 ? 'exceeded' : 'warning'),
                    'severity' => $percentage >= 100 ? 'critical' : 'warning',
                    'title' => $percentage >= 100 ? 'Anggaran terlampaui' : 'Anggaran hampir habis',
                    'message' => ($budget->category?->name ?? 'Kategori').' telah terpakai '.round($percentage).'%.',
                    'url' => route('budgets.index'),
                ]);
            }
        }

        $severity = $issues->contains('severity', 'critical') ? 'critical' : ($issues->isNotEmpty() ? 'warning' : 'healthy');

        return compact('severity', 'issues', 'income', 'expense', 'balance');
    }
}
