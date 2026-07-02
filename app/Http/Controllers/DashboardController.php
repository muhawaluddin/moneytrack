<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\FinancialHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, FinancialHealthService $healthService)
    {
        $user = $request->user();
        $space = $request->attributes->get('space');
        $start = now()->startOfMonth();
        $accounts = $space->visibleAccounts($user)->where('is_active', true)->orderBy('name')->get();
        $transactions = fn () => $space->transactions()->whereIn('account_id', $accounts->pluck('id'));
        $monthTotals = $transactions()->where('status', 'paid')->where('transacted_at', '>=', $start)
            ->selectRaw('type, SUM(amount) total')->groupBy('type')->pluck('total', 'type');
        $recent = $transactions()->with(['account', 'destinationAccount', 'category', 'creator'])->latest('transacted_at')->limit(8)->get();
        $topCategories = $transactions()->where('transactions.type', 'expense')->where('transactions.status', 'paid')->where('transactions.transacted_at', '>=', $start)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')->select('categories.name', 'categories.color', DB::raw('SUM(transactions.amount) total'))
            ->groupBy('categories.id', 'categories.name', 'categories.color')->orderByDesc('total')->limit(5)->get();
        $trend = collect(range(5, 0))->map(function ($ago) use ($transactions) {
            $month = now()->subMonths($ago);
            $rows = $transactions()->where('status', 'paid')->whereBetween('transacted_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->selectRaw('type, SUM(amount) total')->groupBy('type')->pluck('total', 'type');

            return ['label' => $month->translatedFormat('M'), 'income' => (float) ($rows['income'] ?? 0), 'expense' => (float) ($rows['expense'] ?? 0)];
        });
        $budgets = Budget::where('space_id', $space->id)->with('category')->whereDate('month', $start->toDateString())->get()->map(function ($budget) use ($transactions, $start) {
            $budget->spent = $transactions()->where('category_id', $budget->category_id)->where('type', 'expense')->where('status', 'paid')->whereBetween('transacted_at', [$start, now()->endOfMonth()])->sum('amount');

            return $budget;
        });
        $financialHealth = $healthService->evaluate($space, $user);

        return view('dashboard', compact('accounts', 'monthTotals', 'recent', 'topCategories', 'trend', 'budgets', 'financialHealth'));
    }
}
