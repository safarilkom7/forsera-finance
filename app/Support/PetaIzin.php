<?php

namespace App\Support;

/**
 * Peta izin (permission) aplikasi untuk matriks halaman Hak Akses.
 *
 * Izin dikelompokkan per modul dengan deskripsi singkat. Izin yang tercantum
 * di self::DIPAKAI_RUNTIME benar-benar diperiksa oleh middleware/policy, jadi
 * izin khusus yang diberikan langsung berlaku. Izin di luar daftar itu saat
 * ini masih mengikuti aturan peran — tetap ditampilkan supaya kandidat izin
 * yang tersedia terlihat lengkap.
 */
final class PetaIzin
{
    /** @var array<string, array<string, string>> Nama grup => nama izin => deskripsi. */
    public const GRUP = [
        'Dashboard' => [
            'dashboard.lihat' => 'Melihat dashboard',
        ],
        'Pemasukan' => [
            'pemasukan.lihat' => 'Membuka halaman pemasukan',
            'pemasukan.input' => 'Menambah pemasukan',
            'pemasukan.ubah' => 'Mengubah pemasukan',
            'pemasukan.hapus' => 'Menghapus pemasukan tunggal/massal yang belum terkunci dan belum masuk rekonsiliasi',
        ],
        'Pengeluaran' => [
            'pengeluaran.lihat' => 'Membuka halaman pengeluaran',
            'pengeluaran.input' => 'Menambah pengeluaran',
            'pengeluaran.ubah' => 'Mengubah pengeluaran',
            'pengeluaran.hapus' => 'Menghapus pengeluaran tunggal/massal yang belum masuk rekonsiliasi',
            'pengeluaran.setujui' => 'Menyetujui atau menolak pengeluaran yang butuh persetujuan',
        ],
        'Rekonsiliasi' => [
            'rekonsiliasi.lihat' => 'Membuka dan membuat rekonsiliasi',
            'rekonsiliasi.verifikasi' => 'Verifikasi rekonsiliasi',
        ],
        'Insiden' => [
            'insiden.lihat' => 'Membuka halaman insiden',
            'insiden.kelola' => 'Mengelola investigasi dan penyelesaian insiden',
            'write_off' => 'Write-off aset pada penyelesaian insiden',
        ],
        'Analitik' => [
            'laporan.lihat' => 'Membuka laporan dan ekspor Excel/PDF',
        ],
        'Master Data' => [
            'master.kelola' => 'Mengelola unit usaha, metode bayar, kategori, jenis, satuan, dan item',
        ],
        'Administrasi' => [
            'pengguna.kelola' => 'Mengelola akun pengguna dan peran',
            'pengaturan.kelola' => 'Mengatur pengaturan aplikasi',
            'periode.kelola' => 'Menutup dan membuka periode akuntansi',
            'audit.lihat' => 'Melihat log aktivitas',
        ],
        'Lainnya' => [
            'koreksi.terkunci' => 'Koreksi transaksi yang sudah terkunci',
        ],
    ];

    /** Izin yang benar-benar diperiksa middleware/policy saat ini. */
    public const DIPAKAI_RUNTIME = [
        'pemasukan.hapus',
        'pengeluaran.hapus',
        'pengeluaran.setujui',
        'rekonsiliasi.lihat',
        'insiden.lihat',
        'insiden.kelola',
        'laporan.lihat',
        'master.kelola',
        'pengguna.kelola',
        'pengaturan.kelola',
        'periode.kelola',
        'audit.lihat',
        'koreksi.terkunci',
        'write_off',
    ];

    public static function dipakaiRuntime(string $nama): bool
    {
        return in_array($nama, self::DIPAKAI_RUNTIME, true);
    }
}
