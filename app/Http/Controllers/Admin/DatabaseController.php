<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\BlockchainEntry;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function index()
    {
        $totalDocs = Document::count();
        $totalTx   = BlockchainEntry::count();
        $totalActs = ActivityLog::count();
        return view('admin.database', compact('totalDocs', 'totalTx', 'totalActs'));
    }

    public function query(Request $request)
    {
        $sql = trim($request->input('query', ''));
        if (!$sql) {
            return response()->json(['error' => 'Query kosong.']);
        }

        // Only allow SELECT for safety
        if (!preg_match('/^\s*SELECT\b/i', $sql)) {
            return response()->json(['error' => 'Hanya query SELECT yang diizinkan di console ini.']);
        }

        try {
            $results = DB::select($sql);
            $columns = $results ? array_keys((array) $results[0]) : [];
            $rows    = array_map(fn($r) => array_values((array) $r), $results);
            return response()->json([
                'success' => true,
                'columns' => $columns,
                'rows'    => $rows,
                'count'   => count($rows),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
