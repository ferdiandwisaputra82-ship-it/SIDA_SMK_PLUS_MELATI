<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    /**
     * Step 1: Redirect admin to Google's OAuth authorization page.
     * Admin must be Super Admin to connect/disconnect Drive.
     */
    public function connect()
    {
        try {
            $gdrive = new GoogleDriveService();
        } catch (\Throwable $e) {
            Log::error('[GoogleDriveController::connect] ' . $e->getMessage());
            return redirect()->route('admin.upload')
                ->with('error', 'Gagal menginisialisasi Google Drive: ' . $e->getMessage());
        }

        if (!$gdrive->isConfigured()) {
            return redirect()->route('admin.upload')
                ->with('error', 'Google Drive belum dikonfigurasi di .env (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_DRIVE_FOLDER_ID).');
        }

        $authUrl = $gdrive->getAuthorizationUrl();

        ActivityService::log('Google Drive: Mulai proses otorisasi', session('admin_email'), 'system');

        // Redirect admin to Google's consent screen
        return redirect()->away($authUrl);
    }

    /**
     * Step 2: Google redirects back here with ?code=...
     * We exchange the code for tokens and store the refresh_token.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            $error = $request->get('error');
            ActivityService::log('Google Drive: Otorisasi ditolak oleh pengguna', session('admin_email'), 'system');
            return redirect()->route('admin.upload')
                ->with('error', "Otorisasi Google Drive dibatalkan: {$error}");
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('admin.upload')
                ->with('error', 'Kode otorisasi Google tidak ditemukan.');
        }

        try {
            $gdrive = new GoogleDriveService();
            $gdrive->handleCallback($code);

            ActivityService::log('Google Drive: Berhasil dihubungkan', session('admin_email'), 'system');

            return redirect()->route('admin.upload')
                ->with('success', '✅ Google Drive berhasil dihubungkan! Sekarang Anda dapat mengupload dokumen ke Google Drive.');
        } catch (\Exception $e) {
            ActivityService::log('Google Drive: Gagal otorisasi — ' . $e->getMessage(), session('admin_email'), 'system');

            // Provide actionable advice if refresh_token is missing
            $message = $e->getMessage();
            if (str_contains($message, 'refresh_token') || str_contains($message, 'myaccount.google.com')) {
                return redirect()->route('admin.upload')
                    ->with('warning', '⚠️ ' . $message);
            }

            return redirect()->route('admin.upload')
                ->with('error', 'Gagal menghubungkan Google Drive: ' . $message);
        }
    }

    /**
     * Disconnect: revoke and remove stored tokens.
     */
    public function disconnect()
    {
        try {
            $gdrive = new GoogleDriveService();
            $gdrive->disconnect();

            ActivityService::log('Google Drive: Diputus koneksinya', session('admin_email'), 'system');

            return redirect()->route('admin.upload')
                ->with('success', 'Google Drive berhasil diputus. Klik "Hubungkan Google Drive" untuk menghubungkan kembali.');
        } catch (\Throwable $e) {
            Log::error('[GoogleDriveController::disconnect] ' . $e->getMessage());
            return redirect()->route('admin.upload')
                ->with('error', 'Gagal memutus Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Return Drive connection status as JSON (for AJAX polling).
     */
    public function status()
    {
        try {
            $gdrive = new GoogleDriveService();
        } catch (\Throwable $e) {
            Log::error('[GoogleDriveController::status] ' . $e->getMessage());
            return response()->json([
                'configured' => false,
                'connected'  => false,
                'ready'      => false,
                'error'      => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'configured' => $gdrive->isConfigured(),
            'connected'  => $gdrive->isConnected(),
            'ready'      => $gdrive->isReady(),
        ]);
    }

    /**
     * Return a fresh OAuth access_token for use by Google Picker API in the browser.
     * Called via AJAX just before opening the picker.
     */
    public function getAccessToken()
    {
        try {
            $gdrive = new GoogleDriveService();
        } catch (\Throwable $e) {
            Log::error('[GoogleDriveController::getAccessToken] ' . $e->getMessage());
            return response()->json(['error' => 'init_failed', 'message' => $e->getMessage()], 500);
        }

        if (!$gdrive->isConfigured()) {
            return response()->json(['error' => 'not_configured'], 503);
        }

        if (!$gdrive->isConnected()) {
            return response()->json(['error' => 'not_connected'], 401);
        }

        $token = $gdrive->getFreshAccessToken();

        if (!$token) {
            return response()->json([
                'error'   => 'token_failed',
                'message' => 'Token Google Drive tidak valid atau sudah dicabut. Silakan hubungkan ulang.',
            ], 500);
        }

        return response()->json([
            'access_token' => $token,
            'client_id'    => config('services.google.client_id'),
            'api_key'      => config('services.google.api_key', ''),
        ]);
    }
}
