# Forsera Finance

Aplikasi keuangan internal Forsera Group untuk mencatat pemasukan & pengeluaran tiga unit usaha
(Forsera Resto, Kopi Arobi, Forsera Barbershop), mencocokkan setoran (rekonsiliasi), menindaklanjuti
selisih sebagai insiden, dan menghasilkan laporan laba rugi / buku kas.

Stack: Laravel 12, Blade + Tailwind (Vite), MySQL, Spatie Permission, Pest, Laravel Excel, DomPDF.

## Akun login (hasil seeder)

Semua akun contoh memakai password yang sama dan hanya dibuat di environment `local`.

| Peran | Email | Password | Akses utama |
| --- | --- | --- | --- |
| Owner | `owner@forsera.co.id` | `Forsera#2026` | Semua menu, verifikasi, write-off selisih, tutup periode, kelola pengguna |
| Admin Keuangan | `admin@forsera.co.id` | `Forsera#2026` | Input & verifikasi transaksi (maker-checker), rekonsiliasi, insiden, laporan, master data |
| Staf Unit | `staf@forsera.co.id` | `Forsera#2026` | Input pemasukan/pengeluaran unit yang ditugaskan (Forsera Resto) |

Seeder membuat owner dengan penanda `must_change_password`, tetapi pada database demo saat ini penanda
tersebut sudah dimatikan agar login langsung masuk dashboard. Untuk mengaktifkan kembali paksaan ganti
password, set `must_change_password = true` pada user tersebut lewat menu **Pengguna**.

Reset password manual bila lupa:

```bash
php artisan tinker --execute="\App\Models\User::where('email','owner@forsera.co.id')->first()->forceFill(['password'=>'Forsera#2026','must_change_password'=>false])->save();"
```

Membuat akun staf baru paling mudah lewat menu **Pengguna** (Owner/Admin), termasuk penugasan unit
usaha. Staf hanya bisa melihat dan mencatat transaksi unit yang ditugaskan padanya.

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# sesuaikan kredensial database di .env, lalu
php artisan migrate --seed
npm run build          # atau: npm run dev
php artisan serve
```

Seeder bersifat idempoten (`firstOrCreate`), jadi `php artisan db:seed` boleh dijalankan ulang untuk
menambah akun demo tanpa menghapus data transaksi.

## Perintah harian

```bash
php artisan serve              # jalankan aplikasi
npm run dev                    # watch asset
php artisan test               # seluruh test Pest (29 test)
php artisan migrate:fresh --seed
```

## Aturan bisnis inti

- **Maker-checker**: staf dan admin tidak boleh memverifikasi transaksi yang mereka buat sendiri;
  hanya Owner yang bebas. Verifikasi mengubah status pemasukan menjadi `Terverifikasi`.
- **Pengeluaran**: total dihitung di server dari detail item; nominal di atas batas persetujuan
  otomatis berstatus menunggu persetujuan Owner.
- **Batas transaksi & batas persetujuan** diatur di menu Pengaturan, bukan di kode.
- **Periode terkunci**: transaksi bertanggal pada periode yang sudah ditutup tidak bisa diubah tanpa
  izin koreksi khusus, dan setiap koreksi tercatat di log aktivitas.
- **Rekonsiliasi**: selisih di dalam toleransi dianggap sesuai; selisih di luar toleransi otomatis
  membuat insiden dengan kode `INC-YYYYMM-####`.
- **Penyelesaian insiden**: setoran susulan, koreksi pencatatan, atau write-off (hanya Owner).
- **Lampiran** bukti transaksi disimpan di disk privat dan diunduh lewat route terautorisasi.

Detail lengkap: [docs/PLAN.md](docs/PLAN.md).
