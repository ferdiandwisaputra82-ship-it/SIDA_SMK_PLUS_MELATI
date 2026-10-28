<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Config\PermissionConfig;
use App\Services\ActivityService;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!session()->has('admin_logged_in') || !session('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $userRole  = session('admin_role', PermissionConfig::GURU);
        $userEmail = session('admin_email', 'unknown');

        if (!PermissionConfig::hasPermission($userRole, $permission)) {
            ActivityService::log(
                "Akses Ditolak: {$permission}",
                "Role: {$userRole} | Email: {$userEmail}",
                'system',
                $userEmail
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke fitur ini (403 Forbidden).'
                ], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki akses ke fitur tersebut.');
        }

        return $next($request);
    }
}
