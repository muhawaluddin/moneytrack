<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\MoneyTrackNotifier;
use App\Services\MonthlyClosingService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->date('month')?->startOfMonth() ?? now()->startOfMonth();
        $space = $request->attributes->get('space');
        $accountIds = $space->visibleAccounts($request->user())->pluck('id');
        $budgets = $space->id ? Budget::where('space_id', $space->id)->with('category')->whereDate('month', $month)->get() : collect();
        $spent = $space->transactions()->whereIn('account_id', $accountIds)->where('type', 'expense')->where('status', 'paid')->whereBetween('transacted_at', [$month, $month->copy()->endOfMonth()])->selectRaw('category_id,SUM(amount) total')->groupBy('category_id')->pluck('total', 'category_id');
        $categories = $space->categories()->where('type', 'expense')->where('is_active', true)->orderBy('name')->get();

        return view('budgets.index', compact('month', 'budgets', 'spent', 'categories'));
    }

    public function store(Request $request, MoneyTrackNotifier $notifier, MonthlyClosingService $closingService)
    {
        $data = $request->validate(['category_id' => 'required|integer', 'month' => 'required|date_format:Y-m', 'limit_amount' => 'required|numeric|min:1']);
        $space = $request->attributes->get('space');
        $closingService->assertOpen($space->id, $data['month'].'-01', 'month');
        abort_unless($space->categories()->whereKey($data['category_id'])->where('type', 'expense')->exists(), 404);
        $budget = Budget::updateOrCreate(['space_id' => $space->id, 'category_id' => $data['category_id'], 'month' => $data['month'].'-01'], ['user_id' => $request->user()->id, 'limit_amount' => $data['limit_amount']]);
        $budget->load('category');
        $notifier->financialDataChanged($space, $request->user(), 'memperbarui', 'anggaran '.($budget->category?->name ?? 'keluarga'), route('budgets.index'));
        $notifier->financialAlerts($space);

        return back()->with('success', 'Anggaran tersimpan.');
    }

    public function copy(Request $request, MoneyTrackNotifier $notifier, MonthlyClosingService $closingService)
    {
        $month = $request->date('month')?->startOfMonth() ?? now()->startOfMonth();
        $space = $request->attributes->get('space');
        $closingService->assertOpen($space->id, $month, 'month');
        foreach (Budget::where('space_id', $space->id)->whereDate('month', $month->copy()->subMonth())->get() as $budget) {
            Budget::firstOrCreate(['space_id' => $space->id, 'category_id' => $budget->category_id, 'month' => $month], ['user_id' => $request->user()->id, 'limit_amount' => $budget->limit_amount]);
        }
        $notifier->financialDataChanged($space, $request->user(), 'menyalin', 'anggaran bulan sebelumnya', route('budgets.index'));
        $notifier->financialAlerts($space);

        return back()->with('success', 'Anggaran bulan lalu disalin.');
    }

    public function destroy(Request $request, Budget $budget, MoneyTrackNotifier $notifier, MonthlyClosingService $closingService)
    {
        abort_unless($budget->space_id === $request->attributes->get('space')->id, 404);
        $closingService->assertOpen($budget->space_id, $budget->month, 'month');
        $budget->load('category');
        $space = $request->attributes->get('space');
        $subject = 'anggaran '.($budget->category?->name ?? 'keluarga');
        $budget->delete();
        $notifier->financialDataChanged($space, $request->user(), 'menghapus', $subject, route('budgets.index'));

        return back()->with('success', 'Anggaran dihapus.');
    }
}
