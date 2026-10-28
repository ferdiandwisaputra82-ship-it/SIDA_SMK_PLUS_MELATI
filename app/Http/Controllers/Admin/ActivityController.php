<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Config\PermissionConfig;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query();

        // Apply RBAC scoping (Staff & Operator only view own activity logs)
        $query = PermissionConfig::scopeActivityQuery($query);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('target', 'like', "%{$search}%");
            });
        }

        $activities = $query->latest()->paginate(30)->withQueryString();
        $typeConfig = ActivityLog::TYPE_CONFIG;

        return view('admin.activity', compact('activities', 'typeConfig'));
    }
}
