<?php

namespace App\Services;

use App\Models\BlockchainEntry;
use App\Models\Document;
use Illuminate\Support\Str;

class BlockchainService
{
    /**
     * Register a document on the Hyperledger Fabric blockchain
     */
    public static function register(Document $document): BlockchainEntry
    {
        $txId = self::generateTxId($document->id);
        $blockHeight = 4800 + BlockchainEntry::count() + 1;
        $channel = \App\Models\SystemSetting::getValue('fabric_channel', 'documentchannel');
        $status = 'Simulated';

        // Panggil microservice Python Fabric
        try {
            $fabricApiUrl = env('FABRIC_API_URL', 'http://localhost:5001/api/fabric/register');
            $response = \Illuminate\Support\Facades\Http::timeout(10)->post($fabricApiUrl, [
                'docId'     => (string)$document->id,
                'hash'      => $document->hash,
                'ownerName' => $document->owner_name ?? '-',
                'docType'   => $document->document_type ?? 'Dokumen Lainnya',
                'year'      => $document->year ?? date('Y'),
            ]);

            if ($response->ok() && $response->json('success')) {
                $txId        = $response->json('txId');
                $blockHeight = $response->json('blockHeight', $blockHeight);
                $status      = $response->json('simulated') ? 'Simulated' : 'Confirmed';
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("⚠️ Koneksi ke Hyperledger Fabric gagal: " . $e->getMessage() . ". Menggunakan mode Simulasi.");
        }

        $entry = BlockchainEntry::create([
            'tx_id'         => $txId,
            'document_id'   => $document->id,
            'document_type' => $document->document_type,
            'hash'          => $document->hash,
            'owner_name'    => $document->owner_name,
            'action'        => 'REGISTER',
            'block_height'  => $blockHeight,
            'channel'       => $channel,
            'status'        => $status,
        ]);

        return $entry;
    }

    /**
     * Record stamped watermark hash on Hyperledger Fabric blockchain
     */
    public static function recordStampedHash(Document $document, string $newHash): BlockchainEntry
    {
        $txId = self::generateTxId($document->id);
        $blockHeight = 4800 + BlockchainEntry::count() + 1;
        $channel = \App\Models\SystemSetting::getValue('fabric_channel', 'documentchannel');
        $status = 'Simulated';

        // Panggil microservice Python Fabric untuk mencatat blok stempel barcode baru
        try {
            $fabricApiUrl = env('FABRIC_API_URL', 'http://localhost:5001/api/fabric/register');
            $response = \Illuminate\Support\Facades\Http::timeout(10)->post($fabricApiUrl, [
                'docId'     => (string)$document->id,
                'hash'      => $newHash,
                'ownerName' => $document->owner_name ?? '-',
                'docType'   => $document->document_type ?? 'Dokumen Lainnya',
                'year'      => $document->year ?? date('Y'),
            ]);

            if ($response->ok() && $response->json('success')) {
                $txId        = $response->json('txId');
                $blockHeight = $response->json('blockHeight', $blockHeight);
                $status      = $response->json('simulated') ? 'Simulated' : 'Confirmed';
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("⚠️ Pencatatan hash stempel ke Hyperledger Fabric gagal: " . $e->getMessage());
        }

        $entry = BlockchainEntry::create([
            'tx_id'         => $txId,
            'document_id'   => $document->id,
            'document_type' => $document->document_type,
            'hash'          => $newHash,
            'owner_name'    => $document->owner_name,
            'action'        => 'STAMP_WATERMARK',
            'block_height'  => $blockHeight,
            'channel'       => $channel,
            'status'        => $status,
        ]);

        return $entry;
    }

    /**
     * Check if a given SHA-256 hash exists on the blockchain (via Fabric CC / Local DB)
     */
    public static function verifyHash(string $hash): ?Document
    {
        $normalizedInput = strtolower(preg_replace('/[^a-f0-9]/', '', $hash));

        // 1. Coba verifikasi via Hyperledger Fabric
        try {
            $fabricApiUrl = env('FABRIC_API_URL_VERIFY', 'http://localhost:5001/api/fabric/verify');
            $response = \Illuminate\Support\Facades\Http::timeout(5)->post($fabricApiUrl, [
                'hash' => $hash,
            ]);

            if ($response->ok() && $response->json('success') && !$response->json('simulated')) {
                $results = $response->json('results', []);
                if (count($results) > 0) {
                    $docId = $results[0]['docId'];
                    $doc = Document::find($docId);
                    if ($doc) {
                        return $doc;
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("⚠️ Gagal verifikasi hash via Fabric: " . $e->getMessage());
        }

        // 2. Cek di tabel BlockchainEntry (bisa mencakup hash asli maupun hash hasil stempel barcode)
        $blockchainEntry = BlockchainEntry::whereRaw("LOWER(REPLACE(hash, '0x', '')) = ?", [$normalizedInput])
            ->orWhere('tx_id', $hash)
            ->first();

        if ($blockchainEntry && $blockchainEntry->document) {
            return $blockchainEntry->document;
        }

        // 3. Fallback direct match Document model
        $documents = Document::all();
        foreach ($documents as $doc) {
            $normalizedDoc = strtolower(preg_replace('/[^a-f0-9]/', '', $doc->hash));
            if ($normalizedDoc === $normalizedInput || $doc->blockchain_tx_id === $hash) {
                return $doc;
            }
        }
        return null;
    }

    /**
     * Hash a file using SHA-256
     */
    public static function hashFile(\Illuminate\Http\UploadedFile $file): string
    {
        return '0x' . hash_file('sha256', $file->getRealPath());
    }

    /**
     * Hash raw binary/string content
     */
    public static function hashContent(string $content): string
    {
        return '0x' . hash('sha256', $content);
    }

    /**
     * Generate a unique blockchain TX ID
     */
    private static function generateTxId(string $docId): string
    {
        $short = substr(str_replace('-', '', strtolower($docId)), 0, 12);
        return 'tx-' . $short . '-' . Str::random(8);
    }
}
