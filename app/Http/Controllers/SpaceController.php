<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $space = $request->attributes->get('space')->load(['members', 'invitations' => fn ($query) => $query->whereNull('accepted_at')->latest()]);

        return view('spaces.index', compact('space'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:80', 'color' => 'required|string|max:20']);
        $sourceSpace = $request->attributes->get('space');
        $space = Space::create($data + ['owner_id' => $request->user()->id, 'type' => 'family']);
        $space->members()->attach($request->user()->id, ['role' => 'owner', 'joined_at' => now()]);
        $this->copyCategories($sourceSpace, $space, $request->user()->id);
        $request->session()->put('space_id', $space->id);

        return redirect()->route('spaces.index')->with('success', 'Ruang keluarga berhasil dibuat.');
    }

    public function switch(Request $request, Space $space)
    {
        abort_unless($request->user()->spaces()->whereKey($space)->exists(), 404);
        $request->session()->put('space_id', $space->id);

        return redirect()->route('dashboard')->with('success', 'Berpindah ke ruang '.$space->name.'.');
    }

    public function update(Request $request, Space $space)
    {
        abort_unless($request->user()->spaces()->whereKey($space)->exists() && $space->canManage($request->user()), 403);
        $data = $request->validate(['name' => 'required|string|max:80', 'color' => 'required|string|max:20']);
        $space->update($data);
        $space->bumpSyncVersion();

        return back()->with('success', 'Informasi ruang diperbarui.');
    }

    public function destroy(Request $request, Space $space)
    {
        abort_unless($space->type === 'family' && $space->owner_id === $request->user()->id, 403);
        $request->validate(['space_name' => ['required', 'string', function ($attribute, $value, $fail) use ($space) {
            if ($value !== $space->name) {
                $fail('Nama ruang tidak sesuai.');
            }
        }]]);

        DB::transaction(function () use ($space) {
            $space->transactions()->delete();
            $space->accounts()->delete();
            Budget::where('space_id', $space->id)->delete();
            $space->members()->detach();
            $space->delete();
        });
        $request->session()->forget('space_id');

        return redirect()->route('dashboard')->with('success', 'Ruang keluarga dan seluruh data di dalamnya telah dihapus.');
    }

    public function updateMember(Request $request, Space $space, int $user)
    {
        abort_unless($space->owner_id === $request->user()->id && $user !== $space->owner_id && $space->members()->whereKey($user)->exists(), 403);
        $data = $request->validate(['role' => 'required|in:manager,contributor']);
        $space->members()->updateExistingPivot($user, ['role' => $data['role']]);
        $space->bumpSyncVersion();

        return back()->with('success', 'Peran anggota diperbarui.');
    }

    public function removeMember(Request $request, Space $space, int $user)
    {
        abort_unless($space->owner_id === $request->user()->id && $user !== $space->owner_id && $space->members()->whereKey($user)->exists(), 403);
        $space->members()->detach($user);
        $space->bumpSyncVersion();

        return back()->with('success', 'Anggota dikeluarkan dari ruang.');
    }

    private function copyCategories(Space $source, Space $destination, int $userId): void
    {
        $map = [];
        foreach ($source->categories()->orderBy('id')->get() as $category) {
            $copy = $category->replicate(['space_id', 'user_id', 'parent_id']);
            $copy->fill(['space_id' => $destination->id, 'user_id' => $userId, 'parent_id' => null])->save();
            $map[$category->id] = $copy;
        }
        foreach ($source->categories()->whereNotNull('parent_id')->get() as $category) {
            if (isset($map[$category->id], $map[$category->parent_id])) {
                $map[$category->id]->update(['parent_id' => $map[$category->parent_id]->id]);
            }
        }
    }
}
