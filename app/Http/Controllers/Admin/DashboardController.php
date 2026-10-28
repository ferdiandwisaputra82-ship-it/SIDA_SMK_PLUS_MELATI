<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\BlockchainEntry;
use App\Models\ActivityLog;
use App\Config\PermissionConfig;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $docQuery = PermissionConfig::scopeDocumentQuery(Document::query());
        $actQuery = PermissionConfig::scopeActivityQuery(ActivityLog::query());

        $totalDocs      = (clone $docQuery)->count();
        $verified       = (clone $docQuery)->where('status', 'Terverifikasi')->count();
        $pending        = (clone $docQuery)->where('status', 'Menunggu Verifikasi')->count();
        $rejected       = (clone $docQuery)->where('status', 'Ditolak')->count();
        $totalTx        = BlockchainEntry::count();
        $recentDocs     = (clone $docQuery)->latest()->take(5)->get();
        $recentActivity = (clone $actQuery)->latest()->take(10)->get();

        // Per type stats
        $docTypes = Document::TYPES;
        $typeStats = [];
        foreach ($docTypes as $type) {
            $typeStats[$type] = (clone $docQuery)->where('document_type', $type)->count();
        }

        // Weekly data (last 7 days)
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i);
            $total = (clone $docQuery)->whereDate('created_at', $date->format('Y-m-d'))->count();
            $ok    = (clone $docQuery)->whereDate('created_at', $date->format('Y-m-d'))->where('status', 'Terverifikasi')->count();
            $weeklyData[] = [
                'day'   => $date->locale('id')->isoFormat('ddd'),
                'total' => $total,
                'ok'    => $ok,
            ];
        }

        $ocrQueue = session('ocr_queue', []);

        $recentDocsData = $recentDocs->map(function ($doc) {
            $meta = $doc->getTypeMeta();
            return [
                'id' => $doc->id,
                'document_number' => $doc->document_number,
                'owner_name' => $doc->owner_name,
                'document_type' => $doc->document_type,
                'type_emoji' => $meta['emoji'] ?? '📄',
                'year' => $doc->year,
                'status' => $doc->status,
                'created_at_formatted' => $doc->getFormattedUploadedAt(),
                'file_url' => route('admin.documents.file', $doc->id),
                'download_url' => route('admin.documents.download', $doc->id),
                'print_url' => route('admin.documents.print', $doc->id),
                'verify_url' => $doc->hash ? route('verify.hash', $doc->hash) : null,
            ];
        });

        // AJAX response for live dashboard sync
        if (request()->ajax() || request()->wantsJson() || request()->has('ajax')) {
            return response()->json([
                'success' => true,
                'stats' => [
                    'totalDocs' => $totalDocs,
                    'verified' => $verified,
                    'pending' => $pending,
                    'rejected' => $rejected,
                    'totalTx' => $totalTx,
                ],
                'weeklyData' => $weeklyData,
                'typeStats' => $typeStats,
                'recentDocs' => $recentDocsData,
                'recentActivity' => $recentActivity->map(function ($act) {
                    $typeConfig = \App\Models\ActivityLog::TYPE_CONFIG[$act->type] ?? null;
                    return [
                        'id' => $act->id,
                        'action' => $act->action,
                        'user_email' => $act->user_email,
                        'target' => $act->target,
                        'type' => $act->type,
                        'type_emoji' => $typeConfig['emoji'] ?? '📝',
                        'type_color' => $typeConfig['color'] ?? 'bg-gray-50 text-gray-700',
                        'timestamp' => $act->getFormattedTimestamp(),
                        'ip' => $act->ip,
                    ];
                }),
                'ocrQueueCount' => count($ocrQueue),
                'syncedAt' => now()->timezone('Asia/Jakarta')->format('H:i:s') . ' WITA',
            ]);
        }

        return view('admin.dashboard', compact(
            'totalDocs', 'verified', 'pending', 'rejected',
            'totalTx', 'recentDocs', 'recentDocsData', 'recentActivity',
            'typeStats', 'weeklyData', 'ocrQueue'
        ));
    }
}
