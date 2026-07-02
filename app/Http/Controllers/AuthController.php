<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|unique:users', 'password' => ['required', 'confirmed', Password::min(8)]]);
        $user = User::create($data);
        $space = Space::create(['owner_id' => $user->id, 'name' => 'Pribadi '.$user->name, 'type' => 'personal']);
        $space->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $this->seedCategories($user, $space);
        Auth::login($user);
        $request->session()->put('space_id', $space->id);

        return redirect()->route('accounts.create')->with('success', 'Akun Anda siap. Tambahkan sumber kas pertama.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function seedCategories(User $user, Space $space): void
    {
        $income = ['Gaji/Upah', 'Bonus/THR', 'Usaha/Bisnis', 'Freelance/Proyek', 'Investasi', 'Hadiah/Pemberian', 'Pengembalian Dana', 'Penjualan Aset', 'Lainnya'];
        foreach ($income as $i => $name) {
            Category::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => $name, 'type' => 'income', 'sort_order' => $i, 'color' => '#16a34a']);
        }
        $expense = ['Makanan & Minuman', 'Belanja Bulanan', 'Transportasi', 'Bahan Bakar', 'Tempat Tinggal', 'Listrik & Air', 'Internet & Pulsa', 'Kesehatan', 'Pendidikan', 'Keluarga & Anak', 'Zakat & Sedekah', 'Hiburan & Hobi', 'Bisnis/Usaha', 'Cicilan & Utang', 'Pajak & Administrasi', 'Tidak Terduga'];
        foreach ($expense as $i => $name) {
            Category::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => $name, 'type' => 'expense', 'sort_order' => $i, 'color' => '#f97316']);
        }
    }
}
