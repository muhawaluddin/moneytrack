<?php

namespace App\Http\Controllers;

use App\Models\FinancialGoal;
use App\Models\GoalContribution;
use App\Notifications\MoneyTrackNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialGoalController extends Controller
{
    public function index(Request $request)
    {
        $space = $request->attributes->get('space');
        $goals = $space->financialGoals()->with(['contributions.contributor', 'creator'])->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('deadline')->get();

        return view('goals.index', compact('space', 'goals'));
    }

    public function store(Request $request)
    {
        $space = $request->attributes->get('space');
        abort_unless($space->type === 'personal' || $space->canManage($request->user()), 403);
        $data = $request->validate(['name' => 'required|string|max:100', 'target_amount' => 'required|numeric|min:1', 'deadline' => 'nullable|date|after_or_equal:today', 'color' => 'required|string|max:20', 'description' => 'nullable|string|max:500']);
        $space->financialGoals()->create($data + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Target keuangan dibuat.');
    }

    public function contribute(Request $request, FinancialGoal $goal)
    {
        $space = $request->attributes->get('space');
        abort_unless($goal->space_id === $space->id && $goal->status === 'active', 404);
        $data = $request->validate(['amount' => 'required|numeric|min:1', 'contributed_at' => 'required|date', 'notes' => 'nullable|string|max:255']);
        $completed = DB::transaction(function () use ($goal, $space, $request, $data) {
            $locked = FinancialGoal::whereKey($goal)->lockForUpdate()->firstOrFail();
            GoalContribution::create($data + ['space_id' => $space->id, 'financial_goal_id' => $locked->id, 'contributed_by' => $request->user()->id]);
            $locked->increment('current_amount', $data['amount']);
            $completed = (float) $locked->refresh()->current_amount >= (float) $locked->target_amount;
            if ($completed) {
                $locked->update(['status' => 'completed']);
            }

            return $completed;
        });

        if ($completed && $space->type === 'family') {
            foreach ($space->members as $member) {
                if ($member->wantsNotification('goal_updates')) {
                    $member->notify(new MoneyTrackNotification(['kind' => 'goal', 'severity' => 'info', 'space_id' => $space->id, 'title' => 'Target keuangan tercapai', 'message' => 'Target '.$goal->name.' telah mencapai 100%.', 'url' => route('goals.index')]));
                }
            }
        }

        return back()->with('success', $completed ? 'Kontribusi dicatat dan target tercapai.' : 'Kontribusi target dicatat.');
    }

    public function destroy(Request $request, FinancialGoal $goal)
    {
        $space = $request->attributes->get('space');
        abort_unless($goal->space_id === $space->id && ($space->type === 'personal' || $space->canManage($request->user())), 403);
        $goal->delete();

        return back()->with('success', 'Target keuangan dihapus.');
    }
}
