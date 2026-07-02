<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable_id === $request->user()->id && $notification->notifiable_type === $request->user()::class, 404);
        $notification->markAsRead();
        $spaceId = (int) ($notification->data['space_id'] ?? 0);
        if ($spaceId && $request->user()->spaces()->whereKey($spaceId)->exists()) {
            $request->session()->put('space_id', $spaceId);
        }

        return redirect()->to($this->safeUrl($notification->data['url'] ?? route('notifications.index')));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function safeUrl(string $url): string
    {
        return str_starts_with($url, config('app.url')) || str_starts_with($url, '/') ? $url : route('notifications.index');
    }
}
