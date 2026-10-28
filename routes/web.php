<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VerifyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\OcrController;
use App\Http\Controllers\Admin\BlockchainController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ZipBatchController;
use App\Http\Controllers\Admin\GoogleDriveController;

// ─── PUBLIC ROUTES ────────────────────────────────────────────────────────────
// Halaman awal langsung Verifikasi Dokumen
Route::get('/', [VerifyController::class, 'index'])->name('verify.index');

// Public Verify aliases & actions
Route::get('/verify', function () {
    return redirect()->route('verify.index');
});
Route::post('/verify', [VerifyController::class, 'process'])->name('verify.process');
Route::get('/verify/result', [VerifyController::class, 'result'])->name('verify.result');
Route::get('/verify/history', [VerifyController::class, 'history'])->name('verify.history');

// Pratinjau Dokumen Publik untuk Hasil Verifikasi
Route::get('/verify/preview/{id}', [VerifyController::class, 'previewFile'])->name('verify.preview');

// Pemindaian Barcode / QR Code Keaslian Dokumen (Scan to Verify)
Route::get('/verify/{hash}', [VerifyController::class, 'verifyByHash'])->name('verify.hash');
Route::get('/v/{hash}', [VerifyController::class, 'verifyByHash'])->name('verify.short');
Route::get('/scan/{hash}', [VerifyController::class, 'verifyByHash'])->name('verify.scan');

// Profil / Beranda Informasi Sekolah
Route::get('/beranda', [HomeController::class, 'index'])->name('landing');

// ─── AUTH ROUTES ──────────────────────────────────────────────────────────────
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// ─── ADMIN ROUTES (Protected with Auth + RBAC Permissions) ───────────────────
Route::prefix('admin')->middleware('admin.auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard')->middleware('permission:dashboard.view');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('admin.documents')->middleware('permission:documents.view');
    Route::post('/documents', [DocumentController::class, 'store'])->name('admin.documents.store')->middleware('permission:documents.upload');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('admin.documents.edit')->middleware('permission:documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('admin.documents.update')->middleware('permission:documents.edit');
    Route::post('/documents/{document}/approve', [DocumentController::class, 'approve'])->name('admin.documents.approve')->middleware('permission:documents.approve');
    Route::post('/documents/{document}/reject', [DocumentController::class, 'reject'])->name('admin.documents.reject')->middleware('permission:documents.approve');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('admin.documents.destroy')->middleware('permission:documents.delete');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('admin.documents.download')->middleware('permission:documents.download');
    Route::get('/documents/{document}/print', [DocumentController::class, 'print'])->name('admin.documents.print')->middleware('permission:documents.print');
    Route::get('/documents/{document}/file', [DocumentController::class, 'serveFile'])->name('admin.documents.file');
    Route::get('/documents/{document}/stamp', [DocumentController::class, 'stampPage'])->name('admin.documents.stamp-page')->middleware('permission:documents.stamp');
    Route::post('/documents/{document}/stamp-barcode', [DocumentController::class, 'stampBarcode'])->name('admin.documents.stamp-barcode')->middleware('permission:documents.stamp');

    // Upload
    Route::get('/upload', [DocumentController::class, 'uploadForm'])->name('admin.upload')->middleware('permission:upload.page');
    Route::post('/upload', [DocumentController::class, 'upload'])->name('admin.upload.post')->middleware('permission:documents.upload');

    // Batch ZIP Upload
    Route::get('/batch-upload',  [ZipBatchController::class, 'showForm'])->name('admin.batch-upload')->middleware('permission:batch_upload.page');
    Route::post('/batch-upload', [ZipBatchController::class, 'process'])->name('admin.batch-upload.post')->middleware('permission:batch_upload.page');

    // OCR (AJAX endpoint & Review Queue)
    Route::post('/ocr/process', [OcrController::class, 'process'])->name('admin.ocr.process')->middleware('permission:documents.upload');
    Route::get('/ocr-review', [OcrController::class, 'review'])->name('admin.ocr-review')->middleware('permission:ocr_review.page');
    Route::post('/ocr-review/{id}/approve', [OcrController::class, 'approve'])->name('admin.ocr.approve')->middleware('permission:documents.approve');
    Route::delete('/ocr-review/{id}/reject', [OcrController::class, 'reject'])->name('admin.ocr.reject')->middleware('permission:documents.approve');

    // Blockchain
    Route::get('/blockchain', [BlockchainController::class, 'index'])->name('admin.blockchain')->middleware('permission:blockchain.page');
    Route::post('/blockchain/sync', [BlockchainController::class, 'sync'])->name('admin.blockchain.sync')->middleware('permission:blockchain.page');
    Route::delete('/blockchain/clear', [BlockchainController::class, 'sync'])->name('admin.blockchain.clear')->middleware('permission:blockchain.page');

    // Activity
    Route::get('/activity', [ActivityController::class, 'index'])->name('admin.activity')->middleware('permission:activity.view');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('admin.users')->middleware('permission:users.manage');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store')->middleware('permission:users.manage');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update')->middleware('permission:users.manage');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy')->middleware('permission:users.manage');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings')->middleware('permission:settings.manage');
    Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update')->middleware('permission:settings.manage');

    // Google Drive OAuth (connect & disconnect — Super Admin only)
    Route::get('/google-drive/connect',      [GoogleDriveController::class, 'connect'])->name('admin.gdrive.connect');
    Route::get('/google-drive/disconnect',   [GoogleDriveController::class, 'disconnect'])->name('admin.gdrive.disconnect');
    Route::get('/google-drive/status',       [GoogleDriveController::class, 'status'])->name('admin.gdrive.status');
    Route::get('/google-drive/access-token', [GoogleDriveController::class, 'getAccessToken'])->name('admin.gdrive.token');
});

// Google Drive OAuth Callback (outside admin.auth middleware — Google redirects here)
Route::get('/admin/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('admin.gdrive.callback');

// Google Drive Quickstart Callback (for 127.0.0.1 redirect URI registered in Google Cloud Console)
Route::get('/google-drive/quickstart.php', [GoogleDriveController::class, 'callback'])->name('gdrive.quickstart.callback');
