<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected ?GoogleClient $client = null;
    protected ?GoogleDrive  $driveService = null;

    // Keys used in SystemSetting table
    const SETTING_REFRESH_TOKEN  = 'gdrive_refresh_token';
    const SETTING_ACCESS_TOKEN   = 'gdrive_access_token';

    /**
     * Initialize Google Client using OAuth2 Web Application credentials.
     * The client_id and client_secret come from config/services.php (backed by .env).
     * The refresh_token is stored in SystemSetting (obtained after one-time authorization).
     */
    public function __construct()
    {
        if (!$this->isConfigured()) {
            return;
        }

        try {
            // Build client into a LOCAL variable first — only assign to $this->client
            // after the full setup succeeds, so setHttpClient() can never be called on null.
            $client = new GoogleClient();

            // Configure Guzzle HTTP client to bypass local Laragon invalid cacert.pem path error (cURL error 77)
            $httpClient = new \GuzzleHttp\Client([
                'verify'  => false,
                'timeout' => 30,
            ]);
            $client->setHttpClient($httpClient);

            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect', url('/admin/google-drive/callback')));
            $client->addScope(GoogleDrive::DRIVE);
            $client->setAccessType('offline');
            $client->setPrompt('consent'); // Always force refresh_token return

            // Assign to property only after successful setup
            $this->client = $client;

            // Load stored refresh token and initialize drive service
            $refreshToken = SystemSetting::getValue(self::SETTING_REFRESH_TOKEN);
            if ($refreshToken) {
                $tokenData = $this->client->refreshToken($refreshToken);

                if (isset($tokenData['error'])) {
                    Log::warning('[GoogleDriveService] Stored refresh token is invalid: '
                        . ($tokenData['error_description'] ?? $tokenData['error'])
                        . ' — user must reconnect.');
                    // Clear invalid tokens so isConnected() returns false
                    SystemSetting::setValue(self::SETTING_REFRESH_TOKEN, '');
                    SystemSetting::setValue(self::SETTING_ACCESS_TOKEN, '');
                    $this->client = null;
                    return;
                }

                // Persist the refreshed access token
                SystemSetting::setValue(self::SETTING_ACCESS_TOKEN, json_encode($tokenData));

                $this->driveService = new GoogleDrive($this->client);
            }
        } catch (\Throwable $e) {
            Log::error('[GoogleDriveService] Initialization error: ' . $e->getMessage());
            $this->client       = null;
            $this->driveService = null;
        }
    }

    /**
     * Check if client_id, client_secret and folder_id have been configured.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'))
            && !empty(config('services.google.folder_id'));
    }

    /**
     * Check if a refresh_token has been stored (i.e., admin has authorized Drive access).
     */
    public function isConnected(): bool
    {
        return !empty(SystemSetting::getValue(self::SETTING_REFRESH_TOKEN));
    }

    /**
     * Check if the service is fully ready to make API calls.
     */
    public function isReady(): bool
    {
        return $this->isConfigured() && $this->isConnected() && $this->driveService !== null;
    }

    /**
     * Generate the Google OAuth authorization URL for the admin to visit.
     * After authorizing, Google will redirect to our callback URL with a ?code=...
     */
    public function getAuthorizationUrl(): string
    {
        if (!$this->client) {
            // Build a fresh client just for URL generation (also apply Guzzle override for Laragon SSL)
            $client = new GoogleClient();
            $httpClient = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 30]);
            $client->setHttpClient($httpClient);
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect', url('/admin/google-drive/callback')));
            $client->addScope(GoogleDrive::DRIVE);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            return $client->createAuthUrl();
        }
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange the authorization code (from OAuth callback) for tokens.
     * Stores the refresh_token in SystemSetting for future use.
     *
     * @throws \RuntimeException
     */
    public function handleCallback(string $code): void
    {
        // If $this->client is null (constructor failed), build a fresh client for the callback exchange.
        // This ensures OAuth can still complete even if initialization had issues.
        if (!$this->client) {
            if (!$this->isConfigured()) {
                throw new \RuntimeException('Google client tidak terinisialisasi. Periksa GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di .env');
            }
            try {
                $freshClient = new GoogleClient();
                $httpClient  = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 30]);
                $freshClient->setHttpClient($httpClient);
                $freshClient->setClientId(config('services.google.client_id'));
                $freshClient->setClientSecret(config('services.google.client_secret'));
                $freshClient->setRedirectUri(config('services.google.redirect', url('/admin/google-drive/callback')));
                $freshClient->addScope(GoogleDrive::DRIVE);
                $freshClient->setAccessType('offline');
                $freshClient->setPrompt('consent');
                $this->client = $freshClient;
                Log::info('[GoogleDriveService] handleCallback: built fresh client (constructor was null).');
            } catch (\Throwable $e) {
                throw new \RuntimeException('Google client tidak dapat diinisialisasi: ' . $e->getMessage());
            }
        }

        try {
            $tokenData = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($tokenData['error'])) {
                throw new \RuntimeException('Google OAuth error: ' . ($tokenData['error_description'] ?? $tokenData['error']));
            }

            // Store refresh_token (Google only sends this once per authorization, or when prompt=consent is set)
            if (isset($tokenData['refresh_token'])) {
                SystemSetting::setValue(self::SETTING_REFRESH_TOKEN, $tokenData['refresh_token']);
                Log::info('[GoogleDriveService] refresh_token tersimpan.');
            } else {
                // Google didn't send a new refresh_token — check if we already have one stored
                $existing = SystemSetting::getValue(self::SETTING_REFRESH_TOKEN);
                if (!$existing) {
                    // No refresh_token at all — user must disconnect and reconnect to force Google to re-issue one
                    throw new \RuntimeException(
                        'Google tidak mengirimkan refresh_token. Kemungkinan izin sudah pernah diberikan. '
                        . 'Pergi ke https://myaccount.google.com/permissions, cabut akses aplikasi ini, lalu Connect ulang.'
                    );
                }
                Log::warning('[GoogleDriveService] Google tidak mengirim refresh_token baru; menggunakan token lama yang tersimpan.');
            }

            // Store the full token JSON (includes access_token, expiry, etc.)
            SystemSetting::setValue(self::SETTING_ACCESS_TOKEN, json_encode($tokenData));

            Log::info('[GoogleDriveService] Successfully authorized Google Drive access.');
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('[GoogleDriveService] OAuth callback failed: ' . $e->getMessage());
            throw new \RuntimeException('Gagal mengotorisasi Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Get a fresh access token (auto-refreshes using stored refresh_token).
     * Returns the access_token string for use by Google Picker API in the browser.
     */
    public function getFreshAccessToken(): ?string
    {
        if (!$this->client || !$this->isConnected()) {
            return null;
        }

        try {
            $refreshToken = SystemSetting::getValue(self::SETTING_REFRESH_TOKEN);
            $tokenData    = $this->client->refreshToken($refreshToken);

            if (isset($tokenData['error'])) {
                Log::warning('[GoogleDriveService] Token refresh error: '
                    . ($tokenData['error_description'] ?? $tokenData['error'])
                    . ' — token may be revoked. Clearing stored tokens.');
                // Clear invalid tokens so UI shows "not connected"
                SystemSetting::setValue(self::SETTING_REFRESH_TOKEN, '');
                SystemSetting::setValue(self::SETTING_ACCESS_TOKEN, '');
                return null;
            }

            // Save the refreshed access token
            SystemSetting::setValue(self::SETTING_ACCESS_TOKEN, json_encode($tokenData));

            return $tokenData['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('[GoogleDriveService] getFreshAccessToken failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Disconnect: remove stored tokens from SystemSetting and optionally revoke via Google API.
     */
    public function disconnect(): void
    {
        if ($this->client && $this->isConnected()) {
            try {
                $this->client->revokeToken();
            } catch (\Exception $e) {
                // Ignore revocation errors (token may already be invalid)
                Log::warning('[GoogleDriveService] revokeToken failed (ignored): ' . $e->getMessage());
            }
        }
        SystemSetting::setValue(self::SETTING_REFRESH_TOKEN, '');
        SystemSetting::setValue(self::SETTING_ACCESS_TOKEN, '');
        Log::info('[GoogleDriveService] Google Drive disconnected.');
    }

    /**
     * Upload a file to the configured Google Drive folder.
     *
     * @return array{fileId: string, webViewLink: string, downloadUrl: string, fileName: string, mimeType: string}
     * @throws \RuntimeException
     */
    public function uploadFile(UploadedFile $file): array
    {
        if (!$this->isReady()) {
            throw new \RuntimeException(
                'Google Drive belum siap. '
                . ($this->isConfigured()
                    ? 'Hubungkan akun Google Drive terlebih dahulu.'
                    : 'Pastikan GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_DRIVE_FOLDER_ID sudah diisi di .env.')
            );
        }

        $folderId = config('services.google.folder_id');
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        $driveFile = new DriveFile();
        $driveFile->setName($fileName);
        $driveFile->setParents([$folderId]);

        try {
            $result = $this->driveService->files->create(
                $driveFile,
                [
                    'data'       => file_get_contents($file->getRealPath()),
                    'mimeType'   => $mimeType,
                    'uploadType' => 'multipart',
                    'fields'     => 'id, name, webViewLink, mimeType',
                ]
            );

            // Make file accessible via link (reader for anyone)
            $this->setFilePublicReadable($result->getId());

            $fileId = $result->getId();

            Log::info("[GoogleDriveService] Uploaded '{$fileName}' → Drive ID: {$fileId}");

            return [
                'fileId'      => $fileId,
                'webViewLink' => $result->getWebViewLink() ?? $this->getViewUrl($fileId),
                'downloadUrl' => $this->getDownloadUrl($fileId),
                'fileName'    => $result->getName() ?? $fileName,
                'mimeType'    => $result->getMimeType() ?? $mimeType,
            ];
        } catch (\Exception $e) {
            Log::error('[GoogleDriveService] Upload failed: ' . $e->getMessage());
            throw new \RuntimeException('Upload ke Google Drive gagal: ' . $e->getMessage());
        }
    }

    /**
     * Make a Drive file readable by anyone with the link.
     */
    public function setFilePublicReadable(string $fileId): void
    {
        if (!$this->driveService) return;
        try {
            $perm = new \Google\Service\Drive\Permission();
            $perm->setType('anyone');
            $perm->setRole('reader');
            $this->driveService->permissions->create($fileId, $perm, ['fields' => 'id']);
        } catch (\Exception $e) {
            Log::warning("[GoogleDriveService] Could not set public permission for {$fileId}: " . $e->getMessage());
        }
    }

    /**
     * Delete a file from Google Drive.
     */
    public function deleteFile(string $fileId): void
    {
        if (!$this->driveService) return;
        try {
            $this->driveService->files->delete($fileId);
            Log::info("[GoogleDriveService] Deleted Drive file: {$fileId}");
        } catch (\Exception $e) {
            Log::warning("[GoogleDriveService] Delete failed for {$fileId}: " . $e->getMessage());
        }
    }

    /**
     * Read / download raw binary content of a file from Google Drive API.
     */
    public function readFileContent(string $fileId): ?string
    {
        if (!$this->driveService) {
            return null;
        }

        try {
            /** @var \Psr\Http\Message\ResponseInterface $response */
            $response = $this->driveService->files->get($fileId, ['alt' => 'media']);
            return (string) $response->getBody();
        } catch (\Throwable $e) {
            Log::error("[GoogleDriveService] Failed to read file content for {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update existing file on Google Drive with new binary content (e.g., stamped barcode file).
     * If in-place update fails due to permissions (e.g. file owned by different account),
     * automatically falls back to creating a replacement file in the target folder.
     *
     * @return string|false Returns the file ID (original or replacement ID) on success, or false on failure.
     */
    public function updateFileContent(string $fileId, string $localFilePath, string $mimeType, ?string $originalFileName = null): string|false
    {
        if (!$this->isReady() || !file_exists($localFilePath)) {
            return false;
        }

        // 1. Coba update in-place terlebih dahulu
        try {
            $driveFile = new DriveFile();
            $this->driveService->files->update(
                $fileId,
                $driveFile,
                [
                    'data'       => file_get_contents($localFilePath),
                    'mimeType'   => $mimeType,
                    'uploadType' => 'multipart',
                ]
            );
            Log::info("[GoogleDriveService] Successfully updated in-place file content for {$fileId}");
            return $fileId;
        } catch (\Throwable $e) {
            Log::warning("[GoogleDriveService] In-place update failed for {$fileId} ({$e->getMessage()}), attempting fallback create in folder...");
        }

        // 2. Fallback: Buat file baru di folder target jika update in-place ditolak Google (misal beda owner akun)
        try {
            $folderId = config('services.google.folder_id');
            $fileName = $originalFileName;
            if (!$fileName) {
                $meta = $this->getFileMetadata($fileId);
                $fileName = $meta['name'] ?? basename($localFilePath);
            }

            $newDriveFile = new DriveFile();
            $newDriveFile->setName($fileName);
            if ($folderId) {
                $newDriveFile->setParents([$folderId]);
            }

            $created = $this->driveService->files->create(
                $newDriveFile,
                [
                    'data'       => file_get_contents($localFilePath),
                    'mimeType'   => $mimeType,
                    'uploadType' => 'multipart',
                    'fields'     => 'id, name, webViewLink, mimeType',
                ]
            );

            $newId = $created->getId();
            $this->setFilePublicReadable($newId);

            // Coba hapus berkas lama jika diizinkan (abaikan jika tidak memiliki hak hapus)
            try {
                $this->driveService->files->delete($fileId);
            } catch (\Throwable $delEx) {
                Log::notice("[GoogleDriveService] Old file {$fileId} could not be deleted after fallback create: " . $delEx->getMessage());
            }

            Log::info("[GoogleDriveService] Fallback replacement file created for {$fileId} -> new Drive ID {$newId}");
            return $newId;
        } catch (\Throwable $fallbackEx) {
            Log::error("[GoogleDriveService] Fallback file creation also failed for {$fileId}: " . $fallbackEx->getMessage());
            return false;
        }
    }

    /**
     * Get file metadata (name, mimeType, size) from Google Drive API.
     */
    public function getFileMetadata(string $fileId): ?array
    {
        if (!$this->driveService) {
            return null;
        }

        try {
            $file = $this->driveService->files->get($fileId, ['fields' => 'id, name, mimeType, size, webViewLink']);
            return [
                'id'          => $file->getId(),
                'name'        => $file->getName(),
                'mimeType'    => $file->getMimeType(),
                'size'        => $file->getSize(),
                'webViewLink' => $file->getWebViewLink(),
            ];
        } catch (\Throwable $e) {
            Log::warning("[GoogleDriveService] Failed to fetch metadata for {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    // ── URL Helpers ────────────────────────────────────────────────────────

    public function getDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }

    public function getViewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    // ── Static Path Helpers ────────────────────────────────────────────────

    public static function buildFilePath(string $fileId): string
    {
        return "gdrive:{$fileId}";
    }

    public static function parseFileId(string $filePath): ?string
    {
        return str_starts_with($filePath, 'gdrive:') ? substr($filePath, 7) : null;
    }

    public static function isGdriveFile(string $filePath): bool
    {
        return str_starts_with($filePath, 'gdrive:');
    }
}
