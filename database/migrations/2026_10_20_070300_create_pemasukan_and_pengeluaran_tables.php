<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('unit_usaha_id')->constrained('unit_usaha');
            $table->foreignId('kategori_pemasukan_id')->constrained('kategori_pemasukan');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran');
            $table->unsignedBigInteger('nominal');
            $table->string('note', 500)->nullable();
            $table->json('lampiran')->nullable();
            $table->string('status_verifikasi', 30)->default('BELUM_DICEK');
            $table->foreignId('rekonsiliasi_id')->nullable()->constrained('rekonsiliasi')->nullOnDelete();
            $table->foreignId('insiden_id')->nullable()->constrained('insiden')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_usaha_id', 'tanggal']);
            $table->index(['metode_pembayaran_id', 'status_verifikasi']);
            $table->index(['tanggal', 'status_verifikasi']);
        });

        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('unit_usaha_id')->constrained('unit_usaha');
            $table->foreignId('jenis_pengeluaran_id')->constrained('jenis_pengeluaran');
            $table->foreignId('metode_pembayaran_id')->constrained('metode_pembayaran');
            $table->unsignedBigInteger('total_nominal')->default(0);
            $table->boolean('dari_kas_harian')->default(false);
            $table->string('note', 500)->nullable();
            $table->json('lampiran')->nullable();
            $table->string('status_persetujuan', 20)->default('DISETUJUI');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable();
            $table->foreignId('rekonsiliasi_id')->nullable()->constrained('rekonsiliasi')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_usaha_id', 'tanggal']);
            $table->index(['metode_pembayaran_id', 'status_persetujuan']);
            $table->index(['rekonsiliasi_id'], 'pengeluaran_rekonsiliasi_index');
        });

        Schema::create('detail_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_id')->constrained('pengeluaran')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('item');
            $table->decimal('jumlah', 12, 3);
            $table->foreignId('satuan_id')->constrained('satuan');
            $table->unsignedBigInteger('harga_satuan');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();

            $table->index(['item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pengeluaran');
        Schema::dropIfExists('pengeluaran');
        Schema::dropIfExists('pemasukan');
    }
};
