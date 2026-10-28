<?php

namespace App\Models;

use App\Services\GoogleDriveService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'document_type',
        'owner_name',
        'document_number',
        'nisn',
        'year',
        'description',
        'hash',
        'file_path',
        'file_name',
        'file_mime',
        'uploaded_by',
        'blockchain_tx_id',
        'status',
    ];

    // Document types
    const TYPES = [
        'Ijazah',
        'Rapor',
        'Transkrip Nilai',
        'Surat Keterangan',
        'Sertifikat',
        'Surat Tugas',
        'Surat Keputusan',
        'Dokumen Lainnya',
    ];

    const TYPE_META = [
        'Ijazah'           => ['emoji' => '🎓', 'color' => 'text-blue-700',   'bg' => 'bg-blue-50',   'border' => 'border-blue-200'],
        'Rapor'            => ['emoji' => '📊', 'color' => 'text-emerald-700','bg' => 'bg-emerald-50','border' => 'border-emerald-200'],
        'Transkrip Nilai'  => ['emoji' => '📋', 'color' => 'text-violet-700', 'bg' => 'bg-violet-50', 'border' => 'border-violet-200'],
        'Surat Keterangan' => ['emoji' => '📄', 'color' => 'text-amber-700',  'bg' => 'bg-amber-50',  'border' => 'border-amber-200'],
        'Sertifikat'       => ['emoji' => '🏆', 'color' => 'text-orange-700', 'bg' => 'bg-orange-50', 'border' => 'border-orange-200'],
        'Surat Tugas'      => ['emoji' => '📝', 'color' => 'text-cyan-700',   'bg' => 'bg-cyan-50',   'border' => 'border-cyan-200'],
        'Surat Keputusan'  => ['emoji' => '⚖️', 'color' => 'text-red-700',    'bg' => 'bg-red-50',    'border' => 'border-red-200'],
        'Dokumen Lainnya'  => ['emoji' => '📁', 'color' => 'text-gray-700',   'bg' => 'bg-gray-50',   'border' => 'border-gray-200'],
    ];

    public function blockchainEntry()
    {
        return $this->hasOne(BlockchainEntry::class, 'document_id', 'id');
    }

    public function getTypeMeta(): array
    {
        return self::TYPE_META[$this->document_type] ?? self::TYPE_META['Dokumen Lainnya'];
    }

    /**
     * Check if the file is stored on Google Drive (not local storage).
     */
    public function isGdriveFile(): bool
    {
        return !empty($this->file_path) && GoogleDriveService::isGdriveFile($this->file_path);
    }

    /**
     * Extract the Google Drive file ID from file_path.
     * Returns null if the file is not a Drive file.
     */
    public function getGdriveFileId(): ?string
    {
        return $this->isGdriveFile() ? GoogleDriveService::parseFileId($this->file_path) : null;
    }

    public function getFormattedUploadedAt(): string
    {
        return $this->created_at
            ? $this->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') . ' WIB'
            : '—';
    }
}
