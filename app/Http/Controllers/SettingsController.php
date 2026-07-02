<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('settings.index', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => 'required|max:100', 'theme' => 'required|in:light,dark,system', 'currency' => 'required|in:IDR', 'pin' => 'nullable|digits:6']);
        if (! empty($data['pin'])) {
            $data['pin'] = bcrypt($data['pin']);
        } else {
            unset($data['pin']);
        }$request->user()->update($data);

        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function notifications(Request $request)
    {
        $keys = ['invitations', 'transactions', 'data_updates', 'financial_health', 'goal_updates'];
        $request->user()->update(['notification_preferences' => collect($keys)->mapWithKeys(fn ($key) => [$key => $request->boolean($key)])->all()]);

        return back()->with('success', 'Preferensi notifikasi disimpan.');
    }
}
