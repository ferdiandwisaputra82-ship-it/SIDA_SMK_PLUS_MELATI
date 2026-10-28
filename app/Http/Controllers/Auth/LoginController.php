<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $email    = trim($request->input('email', ''));
        $password = $request->input('password', '');

        // ── Cari user dari database admin_users ──────────────────────────────
        $user = AdminUser::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password) && $user->status === 'Aktif') {
            // Update last login
            $user->update(['last_login' => now()->format('d M Y, H:i')]);

            session([
                'admin_logged_in' => true,
                'admin_email'     => $user->email,
                'admin_name'      => $user->name,
                'admin_role'      => $user->role,
                'admin_user_id'   => $user->id,
            ]);

            ActivityService::log('Login berhasil', $user->email, 'login', $user->email);
            return redirect()->route('admin.dashboard')->with('success', "Selamat datang, {$user->name}!");
        }

        // ── Fallback: cek kredensial env untuk akun super admin legacy ───────
        $adminEmail    = env('ADMIN_EMAIL', 'hendra@smkplusmelati.sch.id');
        $adminPassword = env('ADMIN_PASSWORD', 'securepassword');

        if ($email === $adminEmail && $password === $adminPassword) {
            session([
                'admin_logged_in' => true,
                'admin_email'     => $email,
                'admin_name'      => 'Dr. Hendra Wijaya',
                'admin_role'      => 'Super Admin',
                'admin_user_id'   => null,
            ]);
            ActivityService::log('Login Admin (env) berhasil', $email, 'login', $email);
            return redirect()->route('admin.dashboard')->with('success', 'Selamat datang di SIDA!');
        }

        // ── Cek apakah email ada tapi password salah atau nonaktif ───────────
        if ($user && $user->status !== 'Aktif') {
            ActivityService::log("Login ditolak (akun nonaktif): {$email}", $email, 'system');
            return back()->withErrors(['login' => 'Akun Anda dinonaktifkan. Hubungi Super Admin.'])->withInput();
        }

        ActivityService::log("Login gagal: {$email}", $email, 'system');
        return back()->withErrors(['login' => 'Email atau password salah. Coba lagi.'])->withInput();
    }

    public function logout(Request $request)
    {
        $email = session('admin_email');
        ActivityService::log('Admin logout', $email ?? '-', 'system', $email);
        $request->session()->forget([
            'admin_logged_in',
            'admin_email',
            'admin_name',
            'admin_role',
            'admin_user_id',
        ]);
        return redirect()->route('admin.login')->with('success', 'Berhasil keluar dari sistem.');
    }
}
