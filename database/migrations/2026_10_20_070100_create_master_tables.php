<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_usaha', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->string('tipe', 30);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->string('tipe', 20);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('kategori_pemasukan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_usaha_id')->nullable()->constrained('unit_usaha')->nullOnDelete();
            $table->string('nama', 100);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['unit_usaha_id', 'aktif']);
        });

        Schema::create('jenis_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('satuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->string('singkatan', 20);
            $table->timestamps();
        });

        Schema::create('item', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->foreignId('satuan_default_id')->constrained('satuan');
            $table->foreignId('jenis_pengeluaran_id')->nullable()->constrained('jenis_pengeluaran')->nullOnDelete();
            $table->unsignedBigInteger('harga_terakhir')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->string('tipe', 20)->default('string');
            $table->string('deskripsi', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('user_unit_usaha', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_usaha_id')->constrained('unit_usaha')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'unit_usaha_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('last_unit_usaha_id')->references('id')->on('unit_usaha')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_unit_usaha_id']);
        });

        Schema::dropIfExists('user_unit_usaha');
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('item');
        Schema::dropIfExists('satuan');
        Schema::dropIfExists('jenis_pengeluaran');
        Schema::dropIfExists('kategori_pemasukan');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('unit_usaha');
    }
};
