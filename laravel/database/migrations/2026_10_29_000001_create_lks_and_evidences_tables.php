<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lks', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('scope');
            $table->timestamps();
        });

        Schema::create('evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lk_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('document_number')->nullable();
            $table->string('original_filename');
            $table->char('sha256', 64);
            $table->text('drive_url');
            $table->timestamps();
            $table->unique(['lk_id', 'sha256']);
            $table->index('sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidences');
        Schema::dropIfExists('lks');
    }
};
