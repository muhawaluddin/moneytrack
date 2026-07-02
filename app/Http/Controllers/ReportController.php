<?php

namespace App\Http\Controllers;

use App\Services\MonthlyFinancialAuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, MonthlyFinancialAuditService $auditService)
    {
        return view('reports.index', $this->reportData($request, $auditService));
    }

    public function pdf(Request $request, MonthlyFinancialAuditService $auditService)
    {
        $data = $this->reportData($request, $auditService);
        $data['user'] = $request->user();
        $data['space'] = $request->attributes->get('space');
        $data['selectedAccount'] = $data['accountId'] ? $data['accounts']->firstWhere('id', $data['accountId']) : null;
        $data['logo'] = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('app-icon-192.png')));

        return Pdf::loadView('reports.pdf', $data)->setPaper('a4')->download('laporan-moneytrack-'.$data['from']->format('Y-m-d').'-'.$data['to']->format('Y-m-d').'.pdf');
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $space = $request->attributes->get('space');
        $accounts = $space->visibleAccounts($request->user());
        $rows = $space->transactions()->whereIn('account_id', $accounts->pluck('id'))->whereBetween('transacted_at', [$from, $to])->with(['account', 'destinationAccount', 'category', 'creator']);
        if ($request->filled('account_id')) {
            $rows->where('account_id', $request->integer('account_id'));
        }
        $rows = $rows->latest('transacted_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'Jenis', 'Akun', 'Tujuan', 'Kategori', 'Nominal', 'Status', 'Pencatat', 'Catatan']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->transacted_at->format('Y-m-d H:i'), $row->type, $row->account->name, $row->destinationAccount?->name, $row->category?->name, $row->amount, $row->status, $row->creator?->name, $row->description]);
            }
            fclose($out);
        }, 'moneytrack-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function reportData(Request $request, MonthlyFinancialAuditService $auditService): array
    {
        [$from, $to, $period] = $this->period($request);
        $space = $request->attributes->get('space');
        $accounts = $space->visibleAccounts($request->user())->get();
        $accountId = $request->integer('account_id') ?: null;
        abort_if($accountId && ! $accounts->contains('id', $accountId), 404);

        $query = $this->transactionQuery($space->transactions(), $accounts->pluck('id')->all(), $from, $to, $accountId);
        $totals = (clone $query)->selectRaw('type,SUM(amount) total')->groupBy('type')->pluck('total', 'type')->all();
        $expenseRows = (clone $query)->where('type', 'expense')->with(['category', 'account'])->orderBy('transacted_at')->get();
        $expenseTotal = (float) ($totals['expense'] ?? 0);
        $categories = $expenseRows->groupBy(fn ($row) => $row->category_id ?: 'uncategorized')->map(function ($rows, $key) use ($expenseTotal) {
            $category = $rows->first()->category;
            $total = (float) $rows->sum('amount');

            return (object) ['key' => (string) $key, 'name' => $category?->name ?? 'Tanpa kategori', 'color' => $category?->color ?? '#94a3b8', 'total' => $total, 'count' => $rows->count(), 'average' => (float) $rows->avg('amount'), 'share' => $expenseTotal > 0 ? $total / $expenseTotal * 100 : 0];
        })->values();

        $periodSeconds = (int) floor($from->diffInSeconds($to)) + 1;
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subSeconds($periodSeconds - 1);
        $previousExpense = (float) $this->transactionQuery($space->transactions(), $accounts->pluck('id')->all(), $previousFrom, $previousTo, $accountId)->where('type', 'expense')->sum('amount');
        $audit = $auditService->analyze($totals, $previousExpense, $categories, $expenseRows, $from, $to);
        $daily = (clone $query)->whereIn('type', ['income', 'expense'])->get(['type', 'amount', 'transacted_at'])->groupBy(fn ($row) => $row->transacted_at->format('Y-m-d'))->map(fn ($rows) => ['income' => (float) $rows->where('type', 'income')->sum('amount'), 'expense' => (float) $rows->where('type', 'expense')->sum('amount')])->sortKeys();

        return ['from' => $from, 'to' => $to, 'period' => $period, 'totals' => $totals, 'categories' => $categories->sortByDesc('total')->values(), 'frequentCategories' => $categories->sortByDesc('count')->values(), 'daily' => $daily, 'accounts' => $accounts, 'accountId' => $accountId, 'audit' => $audit, 'previousExpense' => $previousExpense];
    }

    private function transactionQuery($query, array $accountIds, $from, $to, ?int $accountId)
    {
        $query->whereIn('account_id', $accountIds)->where('status', 'paid')->whereBetween('transacted_at', [$from, $to]);
        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        return $query;
    }

    private function period(Request $request): array
    {
        $period = $request->input('period', 'this_month');
        if ($period === 'custom' || (! $request->has('period') && ($request->filled('from') || $request->filled('to')))) {
            $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
            $to = $request->date('to')?->endOfDay() ?? now()->endOfMonth();
            abort_if($from->greaterThan($to), 422, 'Tanggal awal harus sebelum tanggal akhir.');

            return [$from, $to, 'custom'];
        }

        return match ($period) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth(), $period],
            'last_30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay(), $period],
            default => [now()->startOfMonth(), now()->endOfMonth(), 'this_month'],
        };
    }
}
