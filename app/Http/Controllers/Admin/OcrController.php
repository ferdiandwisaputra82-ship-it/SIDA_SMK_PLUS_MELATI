<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\SystemSetting;
use App\Services\OcrService;
use App\Services\BlockchainService;
use App\Services\ActivityService;
use App\Config\PermissionConfig;
use Illuminate\Http\Request;

use App\Services\GoogleDriveService;

class OcrController extends Controller
{
    /**
     * AJAX: Process a file through OCR and return JSON
     */
    public function process(Request $request)
    {
        if (!PermissionConfig::can('documents.upload')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $docType = $request->input('document_type');

        if ($request->filled('gdrive_file_id')) {
            $fileId   = $request->input('gdrive_file_id');
            $gdrive   = new GoogleDriveService();

            if (!$gdrive->isReady()) {
                return response()->json(['success' => false, 'message' => 'Google Drive belum diotorisasi atau belum terhubung.'], 400);
            }

            $content  = $gdrive->readFileContent($fileId);
            if ($content === null) {
                return response()->json(['success' => false, 'message' => 'Gagal membaca isi file dari Google Drive API.'], 500);
            }

            $metadata = $gdrive->getFileMetadata($fileId);
            $fileName = $request->input('gdrive_file_name', $metadata['name'] ?? 'gdrive_file');
            $mimeType = $request->input('gdrive_file_mime', $metadata['mimeType'] ?? 'application/pdf');

            $base64   = base64_encode($content);
            $hash     = '0x' . hash('sha256', $content);

            $result = OcrService::analyze($base64, $fileName, $mimeType, null, $docType);

            return response()->json([
                'success'    => true,
                'hash'       => $hash,
                'logs'       => $result['logs'],
                'metadata'   => $result['metadata'],
                'confidence' => $result['confidence'],
            ]);
        }

        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png',
        ]);

        $file      = $request->file('file');
        $base64    = base64_encode(file_get_contents($file->getRealPath()));
        $fileName  = $file->getClientOriginalName();
        $mimeType  = $file->getMimeType();

        $result = OcrService::analyze($base64, $fileName, $mimeType, null, $docType);
        $hash   = BlockchainService::hashFile($file);

        return response()->json([
            'success'    => true,
            'hash'       => $hash,
            'logs'       => $result['logs'],
            'metadata'   => $result['metadata'],
            'confidence' => $result['confidence'],
        ]);
    }

    /**
     * Show OCR review queue page
     */
    public function review()
    {
        if (!PermissionConfig::can('ocr_review.page')) {
            ActivityService::log('Akses Ditolak: Review OCR Queue', session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berwenang mengakses Review OCR.');
        }

        $ocrQueue = session('ocr_queue', []);
        $docTypes = Document::TYPES;
        $typeMeta = Document::TYPE_META;
        return view('admin.ocr-review', compact('ocrQueue', 'docTypes', 'typeMeta'));
    }

    /**
     * Approve a queued OCR document → save to DB
     */
    public function approve(Request $request, string $id)
    {
        if (!PermissionConfig::can('documents.approve')) {
            ActivityService::log("Akses Ditolak: Approve OCR {$id}", session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berwenang menyetujui dokumen.');
        }

        $ocrQueue = session('ocr_queue', []);
        $item = collect($ocrQueue)->firstWhere('id', $id);

        if (!$item) {
            return redirect()->route('admin.ocr-review')->with('error', 'Item tidak ditemukan.');
        }

        $doc = Document::create([
            'document_type'    => $request->document_type ?? $item['detected_type'],
            'owner_name'       => $request->owner_name    ?? $item['metadata']['ownerName'],
            'document_number'  => $request->document_number ?? $item['metadata']['documentNumber'],
            'nisn'             => $request->nisn          ?? $item['metadata']['nisn'],
            'year'             => $request->year          ?? $item['metadata']['year'],
            'description'      => 'Disetujui dari antrian OCR review',
            'hash'             => $item['hash'] ?? ('0x' . bin2hex(random_bytes(32))),
            'file_name'        => $item['file_name'],
            'file_mime'        => 'application/pdf',
            'file_path'        => '',
            'uploaded_by'      => session('admin_email'),
            'blockchain_tx_id' => '',
            'status'           => 'Terverifikasi',
        ]);

        $entry = BlockchainService::register($doc);
        $doc->update(['blockchain_tx_id' => $entry->tx_id]);

        // Remove from queue
        $ocrQueue = array_values(array_filter($ocrQueue, fn($i) => $i['id'] !== $id));
        session(['ocr_queue' => $ocrQueue]);

        ActivityService::log("Setujui OCR: {$doc->owner_name}", $doc->document_number, 'upload');

        return redirect()->route('admin.ocr-review')->with('success', "Dokumen {$doc->owner_name} disimpan ke Blockchain!");
    }

    /**
     * Reject (remove) an OCR queue item
     */
    public function reject(string $id)
    {
        if (!PermissionConfig::can('documents.approve')) {
            ActivityService::log("Akses Ditolak: Reject OCR {$id}", session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berwenang menolak dokumen.');
        }

        $ocrQueue = session('ocr_queue', []);
        $item = collect($ocrQueue)->firstWhere('id', $id);
        $ocrQueue = array_values(array_filter($ocrQueue, fn($i) => $i['id'] !== $id));
        session(['ocr_queue' => $ocrQueue]);

        ActivityService::log('Tolak OCR: ' . ($item['file_name'] ?? $id), $id, 'verify');
        return redirect()->route('admin.ocr-review')->with('success', 'Dokumen berhasil ditolak dari antrian.');
    }
}
