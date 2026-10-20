<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_tutup', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->foreignId('unit_usaha_id')->nullable()->constrained('unit_usaha')->nullOnDelete();
            $table->foreignId('ditutup_oleh')->constrained('users');
            $table->timestamp('ditutup_at');
            $table->foreignId('dibuka_kembali_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibuka_kembali_at')->nullable();
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->index(['tahun', 'bulan', 'unit_usaha_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_tutup');
    }
};
