<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\MoneyTrackNotifier;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $space = $request->attributes->get('space');

        return view('accounts.index', ['accounts' => $space->visibleAccounts($request->user())->orderByDesc('is_active')->orderBy('name')->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accounts.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, MoneyTrackNotifier $notifier)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['space_id'] = $request->attributes->get('space')->id;
        $data['visibility'] = $request->attributes->get('space')->type === 'family' ? 'shared' : 'personal';
        $data['current_balance'] = $data['opening_balance'];
        $account = Account::create($data);
        $space = $request->attributes->get('space');
        if ($account->visibility === 'shared') {
            $notifier->financialDataChanged($space, $request->user(), 'menambahkan', 'sumber kas bersama '.$account->name, route('accounts.index'));
        }
        $notifier->financialAlerts($space);

        return redirect()->route('accounts.index')->with('success', 'Sumber kas berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Account $account)
    {
        $this->accessible($request, $account);
        $transactions = $account->space->transactions()->with(['category', 'account', 'destinationAccount', 'creator'])->where(fn ($q) => $q->where('account_id', $account->id)->orWhere('destination_account_id', $account->id))->latest('transacted_at')->paginate(20);

        return view('accounts.show', compact('account', 'transactions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Account $account)
    {
        $this->editable($request, $account);

        return view('accounts.form', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account, MoneyTrackNotifier $notifier)
    {
        $this->editable($request, $account);
        $wasShared = $account->visibility === 'shared';
        $data = $this->validated($request, false);
        $data['visibility'] = $request->attributes->get('space')->type === 'family' ? 'shared' : 'personal';
        unset($data['opening_balance']);
        $account->update($data);
        $space = $request->attributes->get('space');
        if ($wasShared || $account->visibility === 'shared') {
            $notifier->financialDataChanged($space, $request->user(), 'memperbarui', 'sumber kas bersama '.$account->name, route('accounts.index'));
        }
        $notifier->financialAlerts($space);

        return redirect()->route('accounts.index')->with('success', 'Sumber kas diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Account $account, MoneyTrackNotifier $notifier)
    {
        $this->editable($request, $account);
        $account->update(['is_active' => false]);
        $space = $request->attributes->get('space');
        if ($account->visibility === 'shared') {
            $notifier->financialDataChanged($space, $request->user(), 'mengarsipkan', 'sumber kas bersama '.$account->name, route('accounts.index'));
        }
        $notifier->financialAlerts($space);

        return back()->with('success', 'Sumber kas diarsipkan.');
    }

    private function validated(Request $request, bool $opening = true): array
    {
        return $request->validate(['name' => 'required|string|max:80', 'type' => 'required|in:bank,ewallet,cash,credit,savings,other', 'visibility' => 'required|in:personal,shared', 'bank_name' => 'nullable|string|max:80', 'account_number' => 'nullable|string|max:80', 'opening_balance' => ($opening ? 'required' : 'nullable').'|numeric', 'currency' => 'required|in:IDR', 'color' => 'required|string|max:20', 'icon' => 'nullable|string|max:40', 'notes' => 'nullable|string|max:500', 'is_active' => 'sometimes|boolean']);
    }

    private function accessible(Request $request, Account $account): void
    {
        $space = $request->attributes->get('space');
        abort_unless($account->space_id === $space->id && ($account->user_id === $request->user()->id || $account->visibility === 'shared'), 404);
    }

    private function editable(Request $request, Account $account): void
    {
        $space = $request->attributes->get('space');
        abort_unless($account->space_id === $space->id && ($account->user_id === $request->user()->id || ($account->visibility === 'shared' && $space->canManage($request->user()))), 404);
    }
}
