<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $blueprint) {
            $blueprint->uuid('id')->primary();
            $blueprint->string('document_type');
            $blueprint->string('owner_name');
            $blueprint->string('document_number');
            $blueprint->string('nisn')->nullable();
            $blueprint->string('year');
            $blueprint->text('description')->nullable();
            $blueprint->string('hash');
            $blueprint->string('file_path')->nullable();
            $blueprint->string('file_name')->nullable();
            $blueprint->string('file_mime')->nullable();
            $blueprint->string('uploaded_by');
            $blueprint->string('blockchain_tx_id')->nullable();
            $blueprint->string('status');
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
