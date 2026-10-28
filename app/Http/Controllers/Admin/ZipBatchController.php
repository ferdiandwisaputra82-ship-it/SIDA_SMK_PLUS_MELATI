<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ZipBatchService;
use Illuminate\Http\Request;

class ZipBatchController extends Controller
{
    /**
     * Show the Batch ZIP Upload form page.
     */
    public function showForm()
    {
        return view('admin.batch-upload');
    }

    /**
     * Accept a ZIP file, run the full batch pipeline, and return a JSON report.
     * This endpoint is called via AJAX/fetch from the frontend.
     */
    public function process(Request $request)
    {
        $request->validate([
            'zip_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:102400', // 100 MB max
            ],
        ], [
            'zip_file.required' => 'File ZIP wajib diunggah.',
            'zip_file.mimes'    => 'File harus berformat ZIP.',
            'zip_file.max'      => 'Ukuran file ZIP maksimal 100 MB.',
        ]);

        try {
            $zipFile       = $request->file('zip_file');
            $uploaderEmail = session('admin_email', 'system@sida');

            $report = ZipBatchService::processBatch($zipFile, $uploaderEmail);

            return response()->json([
                'success' => true,
                'report'  => $report,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
