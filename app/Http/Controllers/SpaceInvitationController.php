<?php

namespace App\Http\Controllers;

use App\Models\SpaceInvitation;
use App\Models\User;
use App\Notifications\MoneyTrackNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpaceInvitationController extends Controller
{
    public function store(Request $request)
    {
        $space = $request->attributes->get('space');
        abort_unless($space->canManage($request->user()) && $space->type === 'family', 403);
        $data = $request->validate(['email' => 'required|email', 'role' => 'required|in:manager,contributor']);
        abort_if(strcasecmp($data['email'], $request->user()->email) === 0, 422, 'Anda sudah menjadi anggota.');
        $token = Str::random(48);
        $space->invitations()->where('email', $data['email'])->whereNull('accepted_at')->delete();
        $invitation = $space->invitations()->create($data + ['invited_by' => $request->user()->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7)]);
        $space->bumpSyncVersion();
        $url = route('invitations.accept', $token);
        $invitee = User::whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->first();
        if ($invitee?->wantsNotification('invitations')) {
            $invitee->notify(new MoneyTrackNotification([
                'kind' => 'invitation', 'severity' => 'info', 'space_id' => $space->id, 'invitation_id' => $invitation->id,
                'title' => 'Undangan keluarga',
                'message' => $request->user()->name.' mengundang Anda bergabung ke '.$space->name.'.',
                'url' => $url,
            ]));
        }

        return back()->with('success', 'Undangan dibuat. Bagikan tautan berikut kepada pasangan.')->with('invitation_url', $url);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = SpaceInvitation::with('space')->where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->firstOrFail();
        abort_unless(strcasecmp($invitation->email, $request->user()->email) === 0, 403, 'Masuk menggunakan email yang diundang.');
        $invitation->space->members()->syncWithoutDetaching([$request->user()->id => ['role' => $invitation->role, 'joined_at' => now()]]);
        $invitation->update(['accepted_at' => now()]);
        $invitation->space->bumpSyncVersion();
        $request->user()->unreadNotifications->filter(fn ($notification) => ($notification->data['invitation_id'] ?? null) === $invitation->id)->each->markAsRead();
        $request->session()->put('space_id', $invitation->space_id);

        return redirect()->route('dashboard')->with('success', 'Anda telah bergabung ke '.$invitation->space->name.'.');
    }

    public function destroy(Request $request, SpaceInvitation $invitation)
    {
        $space = $request->attributes->get('space');
        abort_unless($invitation->space_id === $space->id && $space->canManage($request->user()) && $invitation->accepted_at === null, 403);
        $invitation->delete();
        $space->bumpSyncVersion();

        return back()->with('success', 'Undangan dibatalkan.');
    }
}
