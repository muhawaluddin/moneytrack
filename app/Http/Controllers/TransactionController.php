<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MoneyTrackNotifier;
use App\Services\MonthlyClosingService;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $space = $request->attributes->get('space');
        $accounts = $space->visibleAccounts($request->user())->get();
        $query = $space->transactions()->whereIn('account_id', $accounts->pluck('id'))->with(['account', 'destinationAccount', 'category', 'creator']);
        if ($request->filled('highlight')) {
            $query->whereKey((int) $request->highlight);
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('description', 'like', '%'.$request->q.'%')->orWhere('amount', $request->q));
        }
        foreach (['type', 'account_id', 'category_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('transacted_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('transacted_at', '<=', $request->to);
        }

        return view('transactions.index', ['transactions' => $query->latest('transacted_at')->paginate(20)->withQueryString(), 'accounts' => $accounts, 'categories' => $space->categories()->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $space = $request->attributes->get('space');

        return view('transactions.form', ['accounts' => $space->visibleAccounts($request->user())->where('is_active', true)->get(), 'categories' => $space->categories()->where('is_active', true)->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TransactionService $service, MoneyTrackNotifier $notifier, MonthlyClosingService $closingService)
    {
        $space = $request->attributes->get('space');
        $data = $this->validated($request);
        $closingService->assertOpen($space->id, $data['transacted_at']);
        $transaction = $service->create($data, $request->user()->id, $space->id);
        $notifier->transactionChanged($transaction, $request->user(), 'created');
        $notifier->financialAlerts($space);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dicatat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction)
    {
        $this->own($request, $transaction);

        return redirect()->route('transactions.edit', $transaction);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Transaction $transaction)
    {
        $this->own($request, $transaction);

        $space = $request->attributes->get('space');

        return view('transactions.form', ['transaction' => $transaction, 'accounts' => $space->visibleAccounts($request->user())->get(), 'categories' => $space->categories()->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction, TransactionService $service, MoneyTrackNotifier $notifier, MonthlyClosingService $closingService)
    {
        $this->own($request, $transaction);
        $data = $this->validated($request);
        $closingService->assertOpen($transaction->space_id, $transaction->transacted_at);
        $closingService->assertOpen($transaction->space_id, $data['transacted_at']);
        $service->update($transaction, $data, $request->user()->id);
        $notifier->transactionChanged($transaction->refresh(), $request->user(), 'updated');
        $notifier->financialAlerts($request->attributes->get('space'));

        return redirect()->route('transactions.index')->with('success', 'Transaksi diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction, TransactionService $service, MoneyTrackNotifier $notifier, MonthlyClosingService $closingService)
    {
        $this->own($request, $transaction);
        $closingService->assertOpen($transaction->space_id, $transaction->transacted_at);
        $transaction->load(['space.members', 'account']);
        $service->delete($transaction);
        $notifier->transactionChanged($transaction, $request->user(), 'deleted');
        $notifier->financialAlerts($request->attributes->get('space'));

        return back()->with('success', 'Transaksi dihapus dan saldo dikoreksi.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['type' => 'required|in:income,expense,transfer', 'amount' => 'required|numeric|min:1', 'account_id' => 'required|integer', 'destination_account_id' => 'nullable|required_if:type,transfer|integer|different:account_id', 'category_id' => 'nullable|integer', 'transacted_at' => 'required|date', 'description' => 'nullable|string|max:255', 'status' => 'required|in:paid,pending', 'is_recurring' => 'sometimes|boolean', 'recurring_rule' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly,yearly']);
        $data['is_recurring'] = $request->boolean('is_recurring');
        if ($data['type'] === 'transfer') {
            $data['category_id'] = null;
        }

        return $data;
    }

    private function own(Request $request, Transaction $transaction): void
    {
        $space = $request->attributes->get('space');
        $accountVisible = $space->visibleAccounts($request->user())->whereKey($transaction->account_id)->exists();
        $mayChange = $transaction->created_by === $request->user()->id || $space->canManage($request->user());
        abort_unless($transaction->space_id === $space->id && $accountVisible && $mayChange, 404);
    }
}
