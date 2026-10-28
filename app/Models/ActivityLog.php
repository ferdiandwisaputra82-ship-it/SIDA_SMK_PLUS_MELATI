<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'action',
        'user_email',
        'target',
        'type',
        'ip',
    ];

    const TYPES = ['upload', 'verify', 'download', 'delete', 'login', 'edit', 'system'];

    const TYPE_CONFIG = [
        'upload'   => ['color' => 'bg-blue-50 text-blue-700 border-blue-200',     'emoji' => '⬆️'],
        'verify'   => ['color' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'emoji' => '✅'],
        'download' => ['color' => 'bg-violet-50 text-violet-700 border-violet-200', 'emoji' => '⬇️'],
        'delete'   => ['color' => 'bg-red-50 text-red-700 border-red-200',         'emoji' => '🗑️'],
        'login'    => ['color' => 'bg-cyan-50 text-cyan-700 border-cyan-200',      'emoji' => '🔑'],
        'edit'     => ['color' => 'bg-amber-50 text-amber-700 border-amber-200',   'emoji' => '✏️'],
        'system'   => ['color' => 'bg-gray-50 text-gray-700 border-gray-200',      'emoji' => '⚙️'],
    ];

    public function getFormattedTimestamp(): string
    {
        return $this->created_at
            ? $this->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') . ' WIB'
            : '—';
    }
}
