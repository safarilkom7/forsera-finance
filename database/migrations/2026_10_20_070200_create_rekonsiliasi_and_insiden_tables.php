<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->foreignId('unit_usaha_id')->constrained('unit_usaha');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->date('tanggal_diterima')->nullable();
            $table->unsignedBigInteger('total_dicatat')->default(0);
            $table->unsignedBigInteger('potongan_kas')->default(0);
            $table->unsignedBigInteger('biaya_admin')->default(0);
            $table->unsignedBigInteger('total_diterima')->default(0);
            $table->bigInteger('selisih')->default(0);
            $table->string('status', 30)->default('MENUNGGU_SETTLEMENT');
            $table->text('catatan')->nullable();
            $table->json('bukti')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->text('alasan_pembatalan')->nullable();
            $table->timestamps();

            $table->index(['unit_usaha_id', 'metode_pembayaran_id', 'periode_mulai', 'periode_selesai'], 'rekonsiliasi_filter_index');
            $table->index(['status', 'periode_selesai']);
        });

        Schema::create('insiden', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->foreignId('rekonsiliasi_id')->constrained('rekonsiliasi');
            $table->string('jenis', 40);
            $table->string('arah', 10);
            $table->unsignedBigInteger('nominal_selisih');
            $table->unsignedBigInteger('nominal_terselesaikan')->default(0);
            $table->string('status', 20)->default('OPEN');
            $table->text('penyebab')->nullable();
            $table->string('tipe_penyelesaian', 40)->nullable();
            $table->text('catatan_penyelesaian')->nullable();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('lampiran')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditutup_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('insiden_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insiden_id')->constrained('insiden')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 100);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('nominal')->nullable();
            $table->json('lampiran')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['insiden_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insiden_log');
        Schema::dropIfExists('insiden');
        Schema::dropIfExists('rekonsiliasi');
    }
};
