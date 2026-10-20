<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingLogoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsidenController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\JenisPengeluaranController;
use App\Http\Controllers\KategoriPemasukanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MetodePembayaranController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\UnitUsahaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Logo aplikasi tampil di halaman login, jadi harus bisa diakses tanpa login.
Route::get('branding/logo', BrandingLogoController::class)->name('branding.logo');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'formLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('ubah-password', [AuthController::class, 'formUbahPassword'])->name('password.form');
    Route::post('ubah-password', [AuthController::class, 'ubahPassword'])->name('password.ubah');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('pemasukan/ekspor', [PemasukanController::class, 'export'])->name('pemasukan.export');
    Route::resource('pemasukan', PemasukanController::class)->except(['show']);

    Route::get('pengeluaran/ekspor', [PengeluaranController::class, 'export'])->name('pengeluaran.export');
    Route::post('pengeluaran/{pengeluaran}/setujui', [PengeluaranController::class, 'setujui'])->name('pengeluaran.setujui');
    Route::post('pengeluaran/{pengeluaran}/tolak', [PengeluaranController::class, 'tolak'])->name('pengeluaran.tolak');
    Route::resource('pengeluaran', PengeluaranController::class)->except(['show']);

    Route::middleware('role:owner,admin_keuangan')->group(function () {
        Route::get('rekonsiliasi', [RekonsiliasiController::class, 'index'])->name('rekonsiliasi.index');
        Route::get('rekonsiliasi/buat', [RekonsiliasiController::class, 'create'])->name('rekonsiliasi.create');
        Route::post('rekonsiliasi', [RekonsiliasiController::class, 'store'])->name('rekonsiliasi.store');
        Route::get('rekonsiliasi/{rekonsiliasi}', [RekonsiliasiController::class, 'show'])->name('rekonsiliasi.show');
        Route::post('rekonsiliasi/{rekonsiliasi}/batal', [RekonsiliasiController::class, 'batal'])->name('rekonsiliasi.batal');

        Route::get('insiden', [InsidenController::class, 'index'])->name('insiden.index');
        Route::get('insiden/{insiden}', [InsidenController::class, 'show'])->name('insiden.show');
        Route::post('insiden/{insiden}/investigasi', [InsidenController::class, 'investigasi'])->name('insiden.investigasi');
        Route::post('insiden/{insiden}/penanggung-jawab', [InsidenController::class, 'tugaskan'])->name('insiden.tugaskan');
        Route::post('insiden/{insiden}/selesaikan', [InsidenController::class, 'selesaikan'])->name('insiden.selesaikan');

        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/ekspor-excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
        Route::get('laporan/ekspor-pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
    });

    Route::middleware('role:owner,admin_keuangan')->prefix('master')->name('master.')->group(function () {
        Route::resource('unit', UnitUsahaController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('metode', MetodePembayaranController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('kategori', KategoriPemasukanController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('jenis', JenisPengeluaranController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('satuan', SatuanController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('item', ItemController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });

    Route::middleware('role:owner,admin_keuangan')->get('log-aktivitas', [ActivityController::class, 'index'])->name('activity.index');

    Route::middleware('role:owner')->group(function () {
        Route::resource('pengguna', UserController::class)->only(['index', 'create', 'store', 'edit', 'update'])->parameters(['pengguna' => 'user'])->names('users');
        Route::get('pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
        Route::put('pengaturan/identitas', [PengaturanController::class, 'updateBranding'])->name('pengaturan.branding');
        Route::get('periode', [PeriodeController::class, 'index'])->name('periode.index');
        Route::post('periode', [PeriodeController::class, 'store'])->name('periode.store');
        Route::post('periode/{periode}/buka', [PeriodeController::class, 'buka'])->name('periode.buka');
    });

    Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('notifikasi/baca-semua', [NotificationController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('notifikasi/{id}/baca', [NotificationController::class, 'baca'])->name('notifikasi.baca');

    Route::get('profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profil.update');

    Route::get('lampiran/{jenis}/{id}/{index}', AttachmentController::class)
        ->whereNumber('id')
        ->whereNumber('index')
        ->name('lampiran.download');
});
