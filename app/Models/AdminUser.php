<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    const ROLES = ['Super Admin', 'Admin', 'Operator', 'Guru'];

    const ROLE_COLORS = [
        'Super Admin' => 'bg-purple-50 text-purple-700 border-purple-200',
        'Admin'       => 'bg-blue-50 text-blue-700 border-blue-200',
        'Operator'    => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'Guru'        => 'bg-teal-50 text-teal-700 border-teal-200',
    ];

    /**
     * Deskripsi singkat hak akses per role
     */
    const ROLE_PERMISSIONS = [
        'Super Admin' => 'Akses penuh ke semua fitur sistem (termasuk persetujuan dokumen & pembubuhan barcode)',
        'Admin'       => 'Kelola, upload, cetak dokumen, dan pembubuhan barcode (tanpa akses persetujuan dokumen)',
        'Operator'    => 'Upload & kelola dokumen',
        'Guru'        => 'Upload & kelola dokumen',
    ];

    public function getInitials(): string
    {
        $words = explode(' ', $this->name);
        return strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    }

    /**
     * Cek apakah user punya role tertentu
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Cek apakah user punya salah satu dari role-role ini
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
}
