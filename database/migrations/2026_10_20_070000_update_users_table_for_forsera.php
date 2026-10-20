<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
            $table->boolean('aktif')->default(true)->after('must_change_password');
            $table->foreignId('last_unit_usaha_id')->nullable()->after('aktif');
            $table->timestamp('last_login_at')->nullable()->after('last_unit_usaha_id');
            $table->index('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['aktif']);
            $table->dropColumn(['must_change_password', 'aktif', 'last_unit_usaha_id', 'last_login_at']);
        });
    }
};
