<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\BlockchainService;
use App\Services\ActivityService;
use App\Services\OcrService;
use App\Services\GoogleDriveService;
use App\Services\BarcodeWatermarkService;
use App\Config\PermissionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query();

        // Scope query based on role (Staff can only view their own documents)
        $query = PermissionConfig::scopeDocumentQuery($query);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('owner_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('document_type', 'like', "%{$search}%");
            });
        }
        if ($type = $request->get('type')) {
            $query->where('document_type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $documents  = $query->latest()->paginate(20)->withQueryString();
        $docTypes   = Document::TYPES;
        $typeMeta   = Document::TYPE_META;
        $totalCount = PermissionConfig::scopeDocumentQuery(Document::query())->count();

        return view('admin.documents.index', compact('documents', 'docTypes', 'typeMeta', 'totalCount'));
    }

    public function store(Request $request)
    {
        if (!PermissionConfig::can('documents.upload')) {
            ActivityService::log('Akses Ditolak: Upload Dokumen', session('admin_email'), 'system');
            return back()->with('error', 'Anda tidak berwenang mengunggah dokumen.');
        }

        $request->validate([
            'owner_name'      => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'document_type'   => 'required|in:' . implode(',', Document::TYPES),
            'status'          => 'nullable|in:Terverifikasi,Menunggu Verifikasi,Ditolak',
        ]);

        if (!$request->hash) {
            return back()->withErrors(['hash' => 'Upload file dokumen terlebih dahulu!'])->withInput();
        }

        $doc = Document::create([
            'document_type'   => $request->document_type,
            'owner_name'      => $request->owner_name,
            'document_number' => $request->document_number,
            'nisn'            => $request->nisn ?? '',
            'year'            => $request->year ?? date('Y'),
            'description'     => $request->description ?? '',
            'hash'            => $request->hash,
            'file_name'       => $request->file_name ?? '',
            'file_mime'       => $request->file_mime ?? '',
            'file_path'       => $request->file_path ?? '',
            'uploaded_by'     => session('admin_email'),
            'blockchain_tx_id' => '',
            'status'          => 'Menunggu Verifikasi',
        ]);

        $entry = BlockchainService::register($doc);
        $doc->update(['blockchain_tx_id' => $entry->tx_id]);

        // Stempel Barcode / QR Code Verifikasi Otomatis ke Dokumen (hanya jika Terverifikasi)
        if ($doc->status === 'Terverifikasi' && $doc->file_path && Storage::exists($doc->file_path)) {
            $fullDiskPath = Storage::path($doc->file_path);
            $verifyUrl = route('verify.hash', ['hash' => $doc->blockchain_tx_id ?: $doc->hash]);
            BarcodeWatermarkService::stampDocument($fullDiskPath, $doc->file_mime, $doc, $verifyUrl);
            $finalHash = '0x' . hash_file('sha256', $fullDiskPath);
            $doc->update(['hash' => $finalHash]);
        }

        ActivityService::log("Upload: {$doc->owner_name}", "{$doc->document_type} — {$doc->document_number}", 'upload');

        return redirect()->route('admin.documents')->with('success', "{$doc->owner_name} berhasil didaftarkan ke Blockchain!");
    }

    public function edit(Document $document)
    {
        if (!PermissionConfig::can('documents.edit')) {
            ActivityService::log("Akses Ditolak: Edit Dokumen {$document->id}", session('admin_email'), 'system');
            return redirect()->route('admin.documents')->with('error', 'Anda tidak berwenang mengedit metadata dokumen.');
        }

        return view('admin.documents.edit', [
            'document' => $document,
            'docTypes' => Document::TYPES,
        ]);
    }

    public function update(Request $request, Document $document)
    {
        if (!PermissionConfig::can('documents.edit')) {
            ActivityService::log("Akses Ditolak: Update Dokumen {$document->id}", session('admin_email'), 'system');
            return redirect()->route('admin.documents')->with('error', 'Anda tidak berwenang mengedit metadata dokumen.');
        }

        $request->validate([
            'owner_name'      => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'document_type'   => 'required|in:' . implode(',', Document::TYPES),
            'status'          => 'required|in:Terverifikasi,Menunggu Verifikasi,Ditolak',
        ]);

        $document->update($request->only([
            'document_type', 'owner_name', 'document_number',
            'nisn', 'year', 'description', 'status',
        ]));

        ActivityService::log("Edit: {$document->owner_name}", $document->document_number, 'edit');

        return redirect()->route('admin.documents')->with('success', "Dokumen {$document->owner_name} berhasil diperbarui.");
    }

    public function approve(Document $document)
    {
        if (!PermissionConfig::can('documents.approve')) {
            ActivityService::log("Akses Ditolak: Persetujuan Dokumen {$document->id}", session('admin_email'), 'system');
            return redirect()->route('admin.documents')->with('error', 'Anda tidak berwenang memberikan persetujuan dokumen.');
        }

        $document->update(['status' => 'Terverifikasi']);
        ActivityService::log("Setujui Dokumen: {$document->owner_name}", $document->document_number, 'verify');

        return redirect()->route('admin.documents')->with('success', "Dokumen {$document->owner_name} ({$document->document_number}) berhasil disetujui (Terverifikasi)!");
    }

    public function reject(Document $document)
    {
        if (!PermissionConfig::can('documents.approve')) {
            ActivityService::log("Akses Ditolak: Penolakan Dokumen {$document->id}", session('admin_email'), 'system');
            return redirect()->route('admin.documents')->with('error', 'Anda tidak berwenang menolak dokumen.');
        }

        $document->update(['status' => 'Ditolak']);
        ActivityService::log("Tolak Dokumen: {$document->owner_name}", $document->document_number, 'verify');

        return redirect()->route('admin.documents')->with('success', "Dokumen {$document->owner_name} ({$document->document_number}) berhasil ditolak.");
    }

    public function destroy(Document $document)
    {
        // STRICT RULE: Super Admin ONLY can delete documents
        if (!PermissionConfig::can('documents.delete')) {
            ActivityService::log("Akses Ditolak: Percobaan Hapus Dokumen {$document->id} oleh non-SuperAdmin", session('admin_email'), 'system');
            return redirect()->route('admin.documents')->with('error', 'Hanya Super Admin yang berwenang menghapus dokumen.');
        }

        $name = $document->owner_name;

        // Delete from Google Drive if applicable
        if ($document->isGdriveFile()) {
            $fileId = $document->getGdriveFileId();
            if ($fileId) {
                try {
                    (new GoogleDriveService())->deleteFile($fileId);
                } catch (\Exception $e) {
                    // Log but don't block deletion
                }
            }
        } elseif ($document->file_path) {
            Storage::delete($document->file_path);
        }

        $document->delete();
        ActivityService::log("Hapus: {$name}", $document->document_number ?? '', 'delete');

        return redirect()->route('admin.documents')->with('success', "Dokumen {$name} berhasil dihapus.");
    }

    public function download(Document $document)
    {
        ActivityService::log("Download: {$document->owner_name}", $document->file_name, 'download');

        $ext = pathinfo($document->file_name ?: 'document.pdf', PATHINFO_EXTENSION) ?: 'pdf';
        $stampedPath = 'documents/' . $document->id . '_stamped.' . $ext;

        // 1. Jika ada berkas hasil stamp paten di storage lokal, unduh langsung
        if (Storage::exists($stampedPath)) {
            return Storage::download($stampedPath, $document->file_name);
        }

        // 2. Jika berkas berada di Google Drive
        if ($document->isGdriveFile()) {
            $fileId = $document->getGdriveFileId();
            if (!$fileId) {
                return back()->with('error', 'Referensi file Google Drive tidak valid.');
            }
            $downloadUrl = (new GoogleDriveService())->getDownloadUrl($fileId);
            return redirect()->away($downloadUrl);
        }

        // 3. Fallback berkas lokal
        if (!$document->file_path || !Storage::exists($document->file_path)) {
            return back()->with('error', 'File tidak tersedia di storage.');
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    public function print(Document $document)
    {
        if (!PermissionConfig::can('documents.print')) {
            ActivityService::log("Akses Ditolak: Cetak Dokumen {$document->id}", session('admin_email'), 'system');
            return redirect()->route('admin.documents')->with('error', 'Anda tidak berwenang mencetak dokumen.');
        }

        ActivityService::log("Cetak Dokumen: {$document->owner_name}", $document->document_number, 'download');

        $settings = \App\Models\SystemSetting::getAll();
        $meta     = $document->getTypeMeta();
        $hasFile  = $document->isGdriveFile() || ($document->file_path && Storage::exists($document->file_path));
        $isPdf    = $hasFile && str_contains(strtolower($document->file_mime ?? ''), 'pdf');

        return view('admin.documents.print', compact('document', 'settings', 'meta', 'hasFile', 'isPdf'));
    }

    /**
     * Stream the uploaded document file directly from local storage or Google Drive API to the browser.
     * Used by the print page to display PDF/image in an iframe or <img> tag.
     */
    public function serveFile(Document $document)
    {
        if (!PermissionConfig::can('documents.print') && !PermissionConfig::can('documents.view') && !PermissionConfig::can('documents.edit')) {
            abort(403, 'Anda tidak berwenang mengakses file ini.');
        }

        $ext = pathinfo($document->file_name ?: 'document.pdf', PATHINFO_EXTENSION) ?: 'pdf';
        $originalPath = 'documents/' . $document->id . '_original.' . $ext;
        $stampedPath  = 'documents/' . $document->id . '_stamped.' . $ext;

        $noCacheHeaders = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ];

        // Jika dipanggil untuk pratinjau dokumen asli bersih (misal pada halaman stamp)
        if (request()->boolean('original') && Storage::exists($originalPath)) {
            $path = Storage::path($originalPath);
            $mime = $document->file_mime ?: mime_content_type($path);
            return response()->file($path, array_merge($noCacheHeaders, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . ($document->file_name ?: 'document.' . $ext) . '"',
            ]));
        }

        // Prioritas 1: Jika sudah ada salinan stamped paten di storage lokal
        if (Storage::exists($stampedPath)) {
            $path = Storage::path($stampedPath);
            $mime = $document->file_mime ?: mime_content_type($path);
            return response()->file($path, array_merge($noCacheHeaders, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . ($document->file_name ?: 'document.' . $ext) . '"',
            ]));
        }

        // Prioritas 2: File di Google Drive
        if ($document->isGdriveFile()) {
            $fileId = $document->getGdriveFileId();
            if (!$fileId) {
                abort(404, 'Referensi file Google Drive tidak valid.');
            }

            $gdrive = new GoogleDriveService();
            if ($gdrive->isReady()) {
                $content = $gdrive->readFileContent($fileId);
                if ($content !== null) {
                    $mime     = $document->file_mime ?: 'application/pdf';
                    $fileName = $document->file_name ?: 'document.pdf';

                    return response($content, 200, array_merge($noCacheHeaders, [
                        'Content-Type'        => $mime,
                        'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                    ]));
                }
            }

            // Fallback to Google Drive Preview URL
            $viewUrl = $gdrive->getViewUrl($fileId);
            return redirect()->away($viewUrl);
        }

        // Prioritas 3: File lokal
        if (!$document->file_path || !Storage::exists($document->file_path)) {
            abort(404, 'File tidak tersedia di storage.');
        }

        $path     = Storage::path($document->file_path);
        $mime     = $document->file_mime ?: mime_content_type($path);
        $fileName = $document->file_name ?: basename($document->file_path);

        return response()->file($path, array_merge($noCacheHeaders, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]));
    }

    public function uploadForm()
    {
        if (!PermissionConfig::can('upload.page')) {
            ActivityService::log('Akses Ditolak: Halaman Upload Form', session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berwenang mengakses halaman upload.');
        }

        $settings         = \App\Models\SystemSetting::getAll();
        $docTypes         = Document::TYPES;
        $typeMeta         = Document::TYPE_META;
        $gdrive           = new GoogleDriveService();
        $gdriveConfigured = $gdrive->isConfigured();
        $gdriveConnected  = $gdrive->isConnected();
        $gdriveReady      = $gdrive->isReady();
        $googleClientId   = config('services.google.client_id', '');
        $googleApiKey     = config('services.google.api_key', '');

        // Build a diagnostic error message shown in the upload form
        $gdriveError = null;
        if (!$gdriveConfigured) {
            $gdriveError = 'Google Drive belum dikonfigurasi. Isi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_DRIVE_FOLDER_ID di file .env.';
        } elseif (!$gdriveConnected) {
            $gdriveError = 'Google Drive belum terhubung. Klik tombol "Hubungkan Google Drive" di bawah.';
        } elseif (!$gdriveReady) {
            $gdriveError = 'Token Google Drive tidak valid atau sudah kedaluwarsa. Silakan putuskan dan hubungkan ulang.';
        }

        // Pass a fresh access token if Drive is connected and ready
        $gdriveAccessToken = $gdriveReady ? $gdrive->getFreshAccessToken() : null;

        return view('admin.documents.upload', compact(
            'settings', 'docTypes', 'typeMeta',
            'gdriveConfigured', 'gdriveConnected', 'gdriveReady', 'gdriveError',
            'googleClientId', 'googleApiKey', 'gdriveAccessToken'
        ));
    }


    public function upload(Request $request)
    {
        if (!PermissionConfig::can('documents.upload')) {
            ActivityService::log('Akses Ditolak: POST Upload Dokumen', session('admin_email'), 'system');
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berwenang mengunggah dokumen.');
        }

        $uploadSource = $request->input('upload_source', 'gdrive'); // 'gdrive' or 'local'

        $request->validate([
            'document_file'   => $uploadSource === 'local' ? 'required|file|max:20480|mimes:pdf,jpg,jpeg,png' : 'nullable|file|max:20480|mimes:pdf,jpg,jpeg,png',
            'owner_name'      => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'document_type'   => 'required|in:' . implode(',', Document::TYPES),
            'status'          => 'nullable|in:Terverifikasi,Menunggu Verifikasi,Ditolak',
        ]);

        $filePath = '';
        $fileName = '';
        $fileMime = '';
        $hash     = '';

        if ($uploadSource === 'gdrive') {
            // ── Google Drive Upload ──────────────────────────────────────────
            $gdrive = new GoogleDriveService();

            if ($request->filled('gdrive_file_id')) {
                // File picked directly via Google Picker API dialog
                $gdriveFileId = $request->input('gdrive_file_id');
                $filePath     = GoogleDriveService::buildFilePath($gdriveFileId);
                $fileName     = $request->input('gdrive_file_name', 'Google Drive File');
                $fileMime     = $request->input('gdrive_file_mime', 'application/pdf');

                // Try fetching content to calculate real cryptographic hash if not provided
                $hash = $request->input('hash');
                if (!$hash || !str_starts_with($hash, '0x')) {
                    if ($gdrive->isReady()) {
                        $content = $gdrive->readFileContent($gdriveFileId);
                        if ($content !== null) {
                            $hash = '0x' . hash('sha256', $content);
                        }
                    }
                }
                if (!$hash) {
                    $hash = '0x' . bin2hex(random_bytes(32));
                }
            } elseif ($request->hasFile('document_file')) {
                // File uploaded from local PC to Google Drive
                $file = $request->file('document_file');
                $hash = BlockchainService::hashFile($file);

                if ($gdrive->isReady()) {
                    try {
                        $driveResult = $gdrive->uploadFile($file);
                        $filePath    = GoogleDriveService::buildFilePath($driveResult['fileId']);
                        $fileName    = $driveResult['fileName'];
                        $fileMime    = $driveResult['mimeType'];
                    } catch (\Exception $e) {
                        // Fallback to local storage if API call fails
                        $filePath = $file->store('documents', 'local');
                        $fileName = $file->getClientOriginalName();
                        $fileMime = $file->getMimeType();
                    }
                } else {
                    // Drive not yet connected -> store locally seamlessly
                    $filePath = $file->store('documents', 'local');
                    $fileName = $file->getClientOriginalName();
                    $fileMime = $file->getMimeType();
                }
            } else {
                return back()->withErrors(['document_file' => 'Pilih file dokumen atau gunakan Google Drive Picker.'])->withInput();
            }

        } else {
            // ── Local Storage Upload ─────────────────────────────────────────
            if (!$request->hasFile('document_file')) {
                return back()->withErrors(['document_file' => 'File dokumen wajib diupload.'])->withInput();
            }

            $file     = $request->file('document_file');
            $hash     = BlockchainService::hashFile($file);
            $filePath = $file->store('documents', 'local');
            $fileName = $file->getClientOriginalName();
            $fileMime = $file->getMimeType();
        }

        $doc = Document::create([
            'document_type'    => $request->document_type,
            'owner_name'       => $request->owner_name,
            'document_number'  => $request->document_number,
            'nisn'             => $request->nisn ?? '',
            'year'             => $request->year ?? date('Y'),
            'description'      => $request->description ?? '',
            'hash'             => $hash,
            'file_name'        => $fileName,
            'file_mime'        => $fileMime,
            'file_path'        => $filePath,
            'uploaded_by'      => session('admin_email'),
            'blockchain_tx_id' => '',
            'status'           => 'Menunggu Verifikasi',
        ]);

        $entry = BlockchainService::register($doc);
        $doc->update(['blockchain_tx_id' => $entry->tx_id]);

        // Stempel Barcode / QR Code Verifikasi Otomatis ke Dokumen (hanya jika Terverifikasi)
        if ($doc->status === 'Terverifikasi' && $filePath && Storage::exists($filePath)) {
            $fullDiskPath = Storage::path($filePath);
            $verifyUrl = route('verify.hash', ['hash' => $doc->blockchain_tx_id ?: $doc->hash]);
            BarcodeWatermarkService::stampDocument($fullDiskPath, $doc->file_mime, $doc, $verifyUrl);
            $finalHash = '0x' . hash_file('sha256', $fullDiskPath);
            $doc->update(['hash' => $finalHash]);
        }

        $source = $uploadSource === 'gdrive' ? '(Google Drive)' : '(Local)';
        ActivityService::log("Upload {$source}: {$doc->owner_name}", "{$doc->document_type} — {$doc->document_number}", 'upload');

        return redirect()->route('admin.documents')->with('success', "{$doc->owner_name} berhasil didaftarkan ke Blockchain (Menunggu Verifikasi)! {$source}");
    }

    /**
     * Tampilkan halaman visual interaktif untuk menentukan letak pembubuhan barcode secara manual.
     */
    public function stampPage(Document $document)
    {
        if (!PermissionConfig::can('documents.stamp')) {
            return redirect()->route('admin.documents')->with('error', 'Hanya Super Admin dan Admin yang berwenang membubuhi barcode pada dokumen.');
        }

        if ($document->status !== 'Terverifikasi') {
            return redirect()->route('admin.documents')->with('error', 'Dokumen belum disetujui atau berstatus ditolak. Bubuhi barcode hanya dapat dilakukan jika dokumen telah disetujui (Terverifikasi).');
        }

        if (!$document->file_path) {
            return redirect()->route('admin.documents')->with('error', 'Dokumen ini tidak memiliki berkas fisik untuk dibubuhi barcode.');
        }

        if (!$document->isGdriveFile() && !Storage::exists($document->file_path)) {
            return redirect()->route('admin.documents')->with('error', 'Berkas dokumen tidak ditemukan di penyimpanan lokal.');
        }

        $ext = pathinfo($document->file_name ?: 'document.pdf', PATHINFO_EXTENSION) ?: 'pdf';
        $originalPath = 'documents/' . $document->id . '_original.' . $ext;

        // Amankan salinan berkas asli bersih jika belum ada
        if (!Storage::exists($originalPath)) {
            if ($document->isGdriveFile()) {
                $fileId = $document->getGdriveFileId();
                if ($fileId) {
                    $gdrive = new GoogleDriveService();
                    if ($gdrive->isReady()) {
                        $content = $gdrive->readFileContent($fileId);
                        if ($content) {
                            Storage::put($originalPath, $content);
                        }
                    }
                }
            } elseif ($document->file_path && Storage::exists($document->file_path)) {
                Storage::copy($document->file_path, $originalPath);
            }
        }

        $verifyUrl = route('verify.hash', ['hash' => $document->blockchain_tx_id ?: $document->hash]);
        $mime = strtolower($document->file_mime ?: '');
        $name = strtolower($document->file_name ?: '');
        $isPdf = str_contains($mime, 'pdf') || str_ends_with($name, '.pdf');

        return view('admin.documents.stamp', compact('document', 'verifyUrl', 'isPdf'));
    }

    /**
     * Bubuhi atau perbarui Barcode Verifikasi pada berkas dokumen (mendukung koordinat manual, Local & Google Drive).
     */
    public function stampBarcode(Request $request, Document $document)
    {
        if (!PermissionConfig::can('documents.stamp')) {
            return back()->with('error', 'Hanya Super Admin dan Admin yang berwenang membubuhi barcode pada dokumen.');
        }

        if ($document->status !== 'Terverifikasi') {
            return back()->with('error', 'Dokumen belum disetujui atau berstatus ditolak. Bubuhi barcode hanya dapat dilakukan jika dokumen telah disetujui (Terverifikasi).');
        }

        $verifyUrl   = route('verify.hash', ['hash' => $document->blockchain_tx_id ?: $document->hash]);
        $customX     = $request->filled('x_ratio') ? (float)$request->input('x_ratio') : null;
        $customY     = $request->filled('y_ratio') ? (float)$request->input('y_ratio') : null;
        $customPage  = $request->filled('page_num') ? (int)$request->input('page_num') : null;

        $ext = pathinfo($document->file_name ?: 'document.pdf', PATHINFO_EXTENSION) ?: 'pdf';
        $originalPath = 'documents/' . $document->id . '_original.' . $ext;
        $stampedPath  = 'documents/' . $document->id . '_stamped.' . $ext;

        // Pastikan salinan dokumen bersih ada sebagai sumber acuan pembubuhan (mencegah penumpukan barcode berulang)
        if (!Storage::exists($originalPath)) {
            if ($document->isGdriveFile()) {
                $gdrive = new GoogleDriveService();
                $origContent = $gdrive->isReady() ? $gdrive->readFileContent($document->getGdriveFileId()) : null;
                if ($origContent) {
                    Storage::put($originalPath, $origContent);
                }
            } elseif ($document->file_path && Storage::exists($document->file_path)) {
                Storage::copy($document->file_path, $originalPath);
            }
        }

        // Ambil isi dokumen sumber dari file original bersih
        $baseContent = null;
        if (Storage::exists($originalPath)) {
            $baseContent = Storage::get($originalPath);
        } elseif ($document->isGdriveFile()) {
            $gdrive = new GoogleDriveService();
            $baseContent = $gdrive->isReady() ? $gdrive->readFileContent($document->getGdriveFileId()) : null;
        } elseif ($document->file_path && Storage::exists($document->file_path)) {
            $baseContent = Storage::get($document->file_path);
        }

        if (!$baseContent) {
            return back()->with('error', 'Gagal memuat berkas dokumen untuk dibubuhi barcode.');
        }

        // Tulis berkas sementara untuk proses stamping
        $tempFileName = 'temp_stamp_' . $document->id . '_' . time() . '.' . $ext;
        $tempPath = storage_path('app/' . $tempFileName);
        file_put_contents($tempPath, $baseContent);

        $mimeType = $document->file_mime ?: mime_content_type($tempPath);

        $success = BarcodeWatermarkService::stampDocument(
            $tempPath,
            $mimeType,
            $document,
            $verifyUrl,
            $customX,
            $customY,
            $customPage
        );

        if (!$success) {
            @unlink($tempPath);
            return back()->with('error', 'Gagal membubuhkan barcode ke berkas dokumen.');
        }

        // 1. Simpan salinan hasil stamp paten ke storage lokal
        Storage::put($stampedPath, file_get_contents($tempPath));

        $finalHash = '0x' . hash_file('sha256', $tempPath);

        // Catat transaksi pembaruan hash stempel ke Hyperledger Fabric
        $stampedEntry = BlockchainService::recordStampedHash($document, $finalHash);

        $updateData = [
            'hash'             => $finalHash,
            'file_mime'        => $mimeType,
            'blockchain_tx_id' => $stampedEntry->tx_id,
        ];

        // 2. Sinkronisasi ke tempat penyimpanan asli (Google Drive / Storage Lokal)
        if ($document->isGdriveFile()) {
            $fileId = $document->getGdriveFileId();
            if (!$fileId) {
                @unlink($tempPath);
                return back()->with('error', 'ID file Google Drive tidak valid.');
            }

            $gdrive = new GoogleDriveService();
            if (!$gdrive->isReady()) {
                @unlink($tempPath);
                return back()->with('error', 'Koneksi ke Google Drive tidak aktif. Silakan hubungkan Google Drive di menu Pengaturan.');
            }

            $driveResult = $gdrive->updateFileContent($fileId, $tempPath, $mimeType, $document->file_name);
            if ($driveResult === false) {
                @unlink($tempPath);
                return back()->with('error', 'Gagal memperbarui file di Google Drive.');
            }

            // Jika Google Drive mengembalikan ID baru (akibat fallback permission akun)
            if (is_string($driveResult) && $driveResult !== $fileId) {
                $updateData['file_path'] = GoogleDriveService::buildFilePath($driveResult);
            }
        } else {
            // Berkas lokal
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::put($document->file_path, file_get_contents($tempPath));
            } else {
                $document->file_path = $stampedPath;
                $updateData['file_path'] = $stampedPath;
            }
        }

        $document->update($updateData);
        @unlink($tempPath);

        $locInfo = ($customX !== null && $customY !== null)
            ? " pada posisi X: " . round($customX * 100, 1) . "%, Y: " . round($customY * 100, 1) . "%"
            : "";
        $targetStorage = $document->isGdriveFile() ? 'Google Drive' : 'Lokal';
        ActivityService::log("Bubuhi Barcode ({$targetStorage}): {$document->owner_name}", $document->document_number . $locInfo, 'edit');

        return redirect()->route('admin.documents')->with('success', "Barcode verifikasi blockchain berhasil dibubuhkan{$locInfo} ke berkas {$targetStorage} {$document->owner_name}!");
    }
}
