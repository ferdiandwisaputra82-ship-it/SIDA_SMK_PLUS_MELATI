<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockchain_entries', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('tx_id')->unique();
            $blueprint->uuid('document_id');
            $blueprint->string('document_type');
            $blueprint->string('hash');
            $blueprint->string('owner_name');
            $blueprint->string('action');
            $blueprint->integer('block_height');
            $blueprint->string('channel');
            $blueprint->string('status');
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_entries');
    }
};
