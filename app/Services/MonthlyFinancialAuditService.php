<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MonthlyFinancialAuditService
{
    public function analyze(array $totals, float $previousExpense, Collection $categories, Collection $expenses, CarbonInterface $from, CarbonInterface $to): array
    {
        $income = (float) ($totals['income'] ?? 0);
        $expense = (float) ($totals['expense'] ?? 0);
        $savingRate = $income > 0 ? ($income - $expense) / $income * 100 : null;
        $expenseChange = $previousExpense > 0 ? ($expense - $previousExpense) / $previousExpense * 100 : null;
        $findings = collect();
        $score = 100;

        if ($expenses->isEmpty() && $income === 0.0) {
            return ['score' => null, 'status' => 'neutral', 'label' => 'Belum cukup data', 'findings' => $findings, 'saving_rate' => null, 'expense_change' => null, 'largest' => collect(), 'period_days' => (int) floor($from->diffInDays($to)) + 1];
        }

        if ($expense > $income) {
            $score -= 30;
            $findings->push($this->finding('critical', 'Cashflow negatif', 'Pengeluaran lebih besar Rp '.number_format($expense - $income, 0, ',', '.').' daripada pemasukan.', 'Kurangi pengeluaran fleksibel atau tambah pemasukan sebelum periode berikutnya.'));
        } elseif ($savingRate !== null && $savingRate < 10) {
            $score -= 15;
            $findings->push($this->finding('warning', 'Rasio tabungan rendah', 'Hanya '.round($savingRate, 1).'% pemasukan yang tersisa.', 'Targetkan menyisihkan minimal 10–20% pemasukan.'));
        }

        $top = $categories->sortByDesc('total')->first();
        if ($top && $expense > 0 && $top->share >= 40) {
            $score -= 15;
            $findings->push($this->finding('warning', 'Pengeluaran terkonsentrasi', $top->name.' menyerap '.round($top->share).'% dari seluruh pengeluaran.', 'Periksa rincian kategori ini dan tentukan batas bulanan.'));
        }

        $frequent = $categories->sortByDesc('count')->first();
        if ($frequent && $expenses->count() >= 5 && $frequent->count / $expenses->count() * 100 >= 35) {
            $score -= 10;
            $findings->push($this->finding('warning', 'Transaksi terlalu sering', $frequent->name.' muncul '.$frequent->count.' kali dalam periode ini.', 'Gabungkan pembelian atau tetapkan batas frekuensi mingguan.'));
        }

        if ($expenseChange !== null && $expenseChange > 20) {
            $score -= 15;
            $findings->push($this->finding('warning', 'Pengeluaran meningkat', 'Pengeluaran naik '.round($expenseChange).'% dibanding periode sebelumnya.', 'Bandingkan kategori terbesar dengan periode sebelumnya.'));
        }

        $uncategorized = $categories->firstWhere('key', 'uncategorized');
        if ($uncategorized && $uncategorized->count > 0) {
            $score -= 5;
            $findings->push($this->finding('info', 'Transaksi belum dikategorikan', $uncategorized->count.' transaksi belum memiliki kategori.', 'Lengkapi kategori agar audit berikutnya lebih akurat.'));
        }

        $average = (float) $expenses->avg('amount');
        $unusualThreshold = max($average * 2.5, $expense * .2);
        $largest = $expenses->sortByDesc('amount')->take(5)->values();
        $unusual = $expenses->filter(fn ($transaction) => (float) $transaction->amount >= $unusualThreshold && $expenses->count() >= 3);
        if ($unusual->isNotEmpty()) {
            $score -= 10;
            $findings->push($this->finding('info', 'Transaksi bernilai tidak biasa', $unusual->count().' transaksi jauh di atas rata-rata Rp '.number_format($average, 0, ',', '.').'.', 'Pastikan transaksi besar tersebut memang direncanakan.'));
        }

        $score = max(0, $score);
        $status = $score >= 80 ? 'healthy' : ($score >= 55 ? 'warning' : 'critical');
        $label = $status === 'healthy' ? 'Terkendali' : ($status === 'warning' ? 'Perlu perhatian' : 'Perlu tindakan');

        return ['score' => $score, 'status' => $status, 'label' => $label, 'findings' => $findings, 'saving_rate' => $savingRate, 'expense_change' => $expenseChange, 'largest' => $largest, 'period_days' => (int) floor($from->diffInDays($to)) + 1];
    }

    private function finding(string $severity, string $title, string $message, string $action): array
    {
        return compact('severity', 'title', 'message', 'action');
    }
}
