<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidences', function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by']);
            $table->foreignId('uploaded_by')->nullable()->change();
        });

        Schema::table('evidences', function (Blueprint $table): void {
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by']);
            $table->foreignId('uploaded_by')->nullable(false)->change();
        });

        Schema::table('evidences', function (Blueprint $table): void {
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};
