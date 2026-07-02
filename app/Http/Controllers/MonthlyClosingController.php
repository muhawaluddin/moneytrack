<?php

namespace App\Http\Controllers;

use App\Models\MonthlyClosing;
use App\Services\MonthlyClosingService;
use Illuminate\Http\Request;

class MonthlyClosingController extends Controller
{
    public function index(Request $request)
    {
        $space = $request->attributes->get('space');
        $closings = $space->monthlyClosings()->with('closer')->latest('month')->get();

        return view('closings.index', compact('space', 'closings'));
    }

    public function store(Request $request, MonthlyClosingService $service)
    {
        $space = $request->attributes->get('space');
        abort_unless($space->type === 'personal' || $space->canManage($request->user()), 403);
        $data = $request->validate(['month' => 'required|date_format:Y-m', 'notes' => 'nullable|string|max:500']);
        $month = $request->date('month')?->startOfMonth();
        abort_if($month->isFuture(), 422, 'Bulan mendatang belum dapat ditutup.');
        abort_if($space->monthlyClosings()->whereDate('month', $month)->exists(), 422, 'Bulan tersebut sudah ditutup.');
        $service->close($space, $request->user(), $month, $data['notes'] ?? null);
        $space->bumpSyncVersion();

        return back()->with('success', 'Buku '.$month->translatedFormat('F Y').' berhasil ditutup.');
    }

    public function destroy(Request $request, MonthlyClosing $closing)
    {
        $space = $request->attributes->get('space');
        abort_unless($closing->space_id === $space->id && ($space->type === 'personal' || $space->canManage($request->user())), 403);
        $closing->delete();
        $space->bumpSyncVersion();

        return back()->with('success', 'Buku bulanan dibuka kembali.');
    }
}
