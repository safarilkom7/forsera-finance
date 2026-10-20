# Rencana Teknis — Aplikasi Keuangan Forsera Group

Dokumen ini menjelaskan arsitektur, daftar migrasi, service, dan urutan milestone implementasi.

## 1. Ringkasan arsitektur

- **Framework:** Laravel 12 (PHP 8.2+), **Filament 3.3** sebagai panel admin (`/admin`), Livewire 3.
- **Database:** MySQL 8 / MariaDB 10.11 (kolom `json` untuk lampiran). Uang disimpan sebagai `unsignedBigInteger` rupiah tanpa sen.
- **Lokalisasi:** locale `id`, timezone `Asia/Jakarta`, format Rupiah `Rp 1.250.000`, tanggal `20 Okt 2026` (helper `Format`).
- **Otorisasi:** `spatie/laravel-permission` (role: `owner`, `admin_keuangan`, `staf_unit`) + Laravel Policy untuk scoping unit dan aksi khusus.
- **Audit:** `spatie/laravel-activitylog` pada semua model transaksi + log login + unduhan lampiran; log hanya bisa dibaca dari UI.
- **Logika bisnis** berada di `app/Services`, bukan controller/Livewire page.
- **Lampiran** disimpan di disk privat `local` (`storage/app/private/lampiran/...`) dan disajikan lewat route terotorisasi `/lampiran/{model}/{id}/{index}`.
- **Cache dashboard** 60 detik, tag `dashboard`, di-flush oleh observer transaksi.

## 2. Struktur direktori penting

```
app/
  Enums/                     enum backed (status, jenis, arah, tipe)
  Models/                    model Eloquent + relasi + scope
  Services/                  logika bisnis
    PengaturanService.php
    PeriodeService.php
    PemasukanService.php
    PengeluaranService.php
    RekonsiliasiService.php
    InsidenService.php
    DashboardService.php
    LaporanService.php
    NotifikasiService.php
    LampiranService.php
  Policies/                  PemasukanPolicy, PengeluaranPolicy, ...
  Filament/
    Resources/               resource CRUD (master, transaksi, pengguna, periode)
    Pages/                   Dashboard, Pengaturan, Laporan, Rekonsiliasi (wizard)
    Widgets/                 kartu ringkasan, grafik, tabel unit, perlu perhatian
  Exports/                   export Excel (maatwebsite)
  Support/Format.php         format Rupiah/tanggal/angka
database/
  migrations/                lihat daftar di bagian 3
  seeders/                   DatabaseSeeder (produksi), DemoSeeder (local/staging)
  factories/                 factory untuk test & demo
tests/Feature, tests/Unit   Pest
docs/                        PLAN, ASUMSI, ATURAN_BISNIS, PANDUAN_PENGGUNA
```

## 3. Daftar migrasi

| # | Migrasi | Isi |
| --- | --- | --- |
| 1 | `0001_01_01_000000_create_users_table` | users dasar (dari Laravel) |
| 2 | `..._update_users_table_for_forsera` | `must_change_password`, `aktif`, `last_unit_usaha_id`, `last_login_at` |
| 3 | `..._create_master_tables` | `unit_usaha`, `metode_pembayaran`, `kategori_pemasukan`, `jenis_pengeluaran`, `satuan`, `item`, `pengaturan`, `user_unit_usaha` |
| 4 | `..._create_pemasukan_table` | pemasukan + index |
| 5 | `..._create_pengeluaran_tables` | pengeluaran, detail_pengeluaran |
| 6 | `..._create_rekonsiliasi_table` | rekonsiliasi |
| 7 | `..._create_insiden_tables` | insiden, insiden_log |
| 8 | `..._create_periode_tutup_table` | periode_tutup |
| 9 | `..._create_notifications_table` | notifikasi database |
| 10 | permission/activitylog | dari paket Spatie |

Soft delete: `pemasukan`, `pengeluaran`. `rekonsiliasi`/`insiden` memakai status pembatalan (tidak dihapus).

## 4. Service dan tanggung jawab

- **PengaturanService** — baca/tulis `pengaturan` bertipe, cache, invalidasi.
- **PeriodeService** — `terkunci(tanggal, unit)`, `pastikanTerbuka()`, `tutup()`, `bukaKembali()`.
- **PemasukanService** — validasi sisi server, simpan lampiran, kunci status, koreksi Owner (audit nilai lama/baru), soft delete.
- **PengeluaranService** — detail item, subtotal & total server-side, `harga_terakhir`, deteksi lonjakan harga, alur persetujuan.
- **RekonsiliasiService** — kandidat pemasukan, `potonganKas`, preview selisih, simpan (status + insiden otomatis), tandai menunggu settlement, batal, hitung ulang, koreksi terkait.
- **InsidenService** — transisi OPEN→INVESTIGASI→SELESAI/DIHAPUSKAN, timeline, semua tipe penyelesaian (termasuk bertahap & write-off Owner), sinkronisasi status rekonsiliasi/pemasukan.
- **DashboardService** — agregasi SQL (bukan loop), perbandingan periode, panel Perlu Perhatian, cache 60 detik.
- **LaporanService** — lima laporan bagian 10, menghormati filter & peran.
- **NotifikasiService** — notifikasi in-app (insiden baru, verifikasi terlambat, settlement terlambat, persetujuan pengeluaran).
- **LampiranService** — validasi mime/ukuran, nama acak, penyimpanan privat, metadata JSON.

Semua operasi multi-langkah memakai `DB::transaction`. Enum PHP backed untuk semua status.

## 5. Urutan milestone

1. **M0 Fondasi** — proyek, paket, panel, peran/izin, lokalisasi, pengaturan, activity log, login & ganti password.
2. **M1 Master & pengguna** — CRUD master, pengguna, penugasan unit, riwayat harga item.
3. **M2 Transaksi** — pemasukan/pengeluaran, validasi, lampiran, penguncian, periode, persetujuan, peringatan harga.
4. **M3 Rekonsiliasi & insiden** — wizard, rumus, toleransi, insiden otomatis, timeline, penyelesaian, batal, koreksi, notifikasi.
5. **M4 Dashboard & laporan** — widget dashboard, panel Perlu Perhatian, lima laporan, ekspor Excel/PDF.
6. **M5 Hardening** — demo seeder, backup, performa, dokumentasi, uji manual siklus penuh.

## 6. Pengujian

Pest feature test mencakup rumus selisih, insiden otomatis, semua tipe penyelesaian, koreksi/pembatalan rekonsiliasi, periode tertutup, pengeluaran detail & lonjakan harga, otorisasi per peran (termasuk IDOR lampiran), validasi, dan angka dashboard. Larastan level 6 dan Pint dijalankan pada CI lokal (`composer test`, `composer lint`).
