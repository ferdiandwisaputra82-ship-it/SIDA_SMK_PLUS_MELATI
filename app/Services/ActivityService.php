<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityService
{
    public static function log(string $action, ?string $target, string $type, ?string $userEmail = null): ActivityLog
    {
        return ActivityLog::create([
            'action'     => $action,
            'user_email' => $userEmail ?? session('admin_email', 'system'),
            'target'     => $target ?? '-',
            'type'       => $type,
            'ip'         => Request::ip() ?? '127.0.0.1',
        ]);
    }
}
