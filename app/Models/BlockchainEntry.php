<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainEntry extends Model
{
    protected $fillable = [
        'tx_id',
        'document_id',
        'document_type',
        'hash',
        'owner_name',
        'action',
        'block_height',
        'channel',
        'status',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id');
    }

    public function getFormattedTimestamp(): string
    {
        return $this->created_at
            ? $this->created_at->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') . ' WIB'
            : '—';
    }
}
