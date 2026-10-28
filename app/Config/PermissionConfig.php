<?php

namespace App\Config;

class PermissionConfig
{
    // Role Constants
    public const SUPER_ADMIN    = 'Super Admin';
    public const ADMIN          = 'Admin';
    public const OPERATOR_TU    = 'Operator';       // Operator / Operator/TU
    public const GURU           = 'Guru';           // Guru / Guru-Guru

    // List of valid roles
    public const ALL_ROLES = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::OPERATOR_TU,
        self::GURU,
    ];

    /**
     * Single Source of Truth: Role & Permission Matrix
     */
    public const MATRIX = [
        // Dashboard
        'dashboard.view' => [
            self::SUPER_ADMIN, self::ADMIN, self::OPERATOR_TU, self::GURU
        ],

        // Document Management
        'documents.view' => [
            self::SUPER_ADMIN, self::ADMIN, self::OPERATOR_TU, self::GURU
        ],
        'documents.upload' => [
            self::SUPER_ADMIN, self::ADMIN, self::OPERATOR_TU, self::GURU
        ],
        'documents.approve' => [
            self::SUPER_ADMIN
        ],
        'documents.stamp' => [
            self::SUPER_ADMIN, self::ADMIN
        ],
        'documents.edit' => [
            self::SUPER_ADMIN, self::ADMIN
        ],
        'documents.delete' => [
            self::SUPER_ADMIN
        ],
        'documents.download' => [
            self::SUPER_ADMIN, self::ADMIN, self::OPERATOR_TU, self::GURU
        ],
        'documents.print' => [
            self::SUPER_ADMIN, self::ADMIN
        ],

        // Standalone Modules
        'upload.page' => [
            self::SUPER_ADMIN, self::ADMIN, self::OPERATOR_TU, self::GURU
        ],
        'batch_upload.page' => [
            self::SUPER_ADMIN, self::OPERATOR_TU, self::GURU
        ],
        'ocr_review.page' => [
            self::SUPER_ADMIN
        ],
        'blockchain.page' => [
            self::SUPER_ADMIN
        ],
        'activity.view' => [
            self::SUPER_ADMIN, self::ADMIN, self::OPERATOR_TU, self::GURU
        ],
        'users.manage' => [
            self::SUPER_ADMIN
        ],
        'settings.manage' => [
            self::SUPER_ADMIN
        ],
    ];

    /**
     * Normalize role string (handles variants and legacy names)
     */
    public static function normalizeRole(?string $role): string
    {
        if (!$role) {
            return self::GURU;
        }
        $r = trim($role);
        if ($r === 'Operator/TU' || $r === 'Operator TU' || $r === 'TU') {
            return self::OPERATOR_TU;
        }
        if ($r === 'Guru-Guru' || $r === 'Guru Guru' || $r === 'Staff' || $r === 'staff') {
            return self::GURU;
        }
        return $r;
    }

    /**
     * Check if a given role has a specific permission
     */
    public static function hasPermission(?string $role, string $permission): bool
    {
        $normalized = self::normalizeRole($role);
        
        // Super Admin gets unrestricted access
        if ($normalized === self::SUPER_ADMIN) {
            return true;
        }

        $allowedRoles = self::MATRIX[$permission] ?? [];
        return in_array($normalized, $allowedRoles, true);
    }

    /**
     * Helper to check permission for currently logged-in admin
     */
    public static function can(string $permission): bool
    {
        $currentRole = session('admin_role', self::GURU);
        return self::hasPermission($currentRole, $permission);
    }

    /**
     * Scope document query based on logged-in user role
     */
    public static function scopeDocumentQuery($query)
    {
        // Super Admin, Admin, Operator/TU, Guru can view documents
        return $query;
    }

    /**
     * Scope activity log query based on logged-in user role
     */
    public static function scopeActivityQuery($query)
    {
        $role  = self::normalizeRole(session('admin_role'));
        $email = session('admin_email');

        if (in_array($role, [self::GURU, self::OPERATOR_TU], true)) {
            // Guru & Operator can only view their own activity logs
            return $query->where('user_email', $email);
        }

        // Super Admin, Admin view all activity logs
        return $query;
    }
}
