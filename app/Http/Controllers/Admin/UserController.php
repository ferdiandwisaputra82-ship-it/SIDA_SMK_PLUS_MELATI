<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\ActivityService;
use App\Config\PermissionConfig;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!PermissionConfig::can('users.manage')) {
            ActivityService::log('Akses Ditolak: Halaman Manajemen User', session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Hanya Super Admin yang dapat mengelola pengguna.');
        }

        $query = AdminUser::query();
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
        }
        $users = $query->get();
        $roles = AdminUser::ROLES;
        $roleColors = AdminUser::ROLE_COLORS;
        return view('admin.users', compact('users', 'roles', 'roleColors'));
    }

    public function store(Request $request)
    {
        if (!PermissionConfig::can('users.manage')) {
            ActivityService::log('Akses Ditolak: Tambah User Baru', session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Hanya Super Admin yang dapat menambahkan pengguna.');
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:admin_users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:' . implode(',', AdminUser::ROLES),
        ]);

        $user = AdminUser::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => bcrypt($request->password),
            'role'       => $request->role,
            'status'     => $request->status ?? 'Aktif',
            'last_login' => '—',
        ]);
        ActivityService::log("Tambah User: {$user->name}", $user->email, 'system');
        return redirect()->route('admin.users')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, AdminUser $user)
    {
        if (!PermissionConfig::can('users.manage')) {
            ActivityService::log("Akses Ditolak: Edit User {$user->id}", session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Hanya Super Admin yang dapat mengubah pengguna.');
        }

        $user->update($request->only(['name', 'email', 'role', 'status']));
        ActivityService::log("Edit User: {$user->name}", $user->email, 'edit');
        return redirect()->route('admin.users')->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(AdminUser $user)
    {
        if (!PermissionConfig::can('users.manage')) {
            ActivityService::log("Akses Ditolak: Hapus User {$user->id}", session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Hanya Super Admin yang dapat menghapus pengguna.');
        }

        $name = $user->name;
        $user->delete();
        ActivityService::log("Hapus User: {$name}", $user->email, 'delete');
        return redirect()->route('admin.users')->with('success', "Pengguna {$name} berhasil dihapus.");
    }
}
