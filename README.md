# Forsera Finance

Aplikasi keuangan internal Forsera Group untuk mencatat pemasukan & pengeluaran tiga unit usaha
(Forsera Resto, Kopi Arobi, Forsera Barbershop), mencocokkan setoran (rekonsiliasi), menindaklanjuti
selisih sebagai insiden, dan menghasilkan laporan laba rugi / buku kas.

Stack: Laravel 12, Blade + Tailwind (Vite), MySQL, Spatie Permission, Pest, Laravel Excel, DomPDF.

## Akun login (hasil seeder)

Semua akun contoh memakai password yang sama dan hanya dibuat di environment `local`.

| Peran | Email | Password | Akses utama |
| --- | --- | --- | --- |
| Owner | `owner@forsera.co.id` | `Forsera#2026` | Semua menu, verifikasi, write-off selisih, tutup periode, kelola pengguna, hapus transaksi & master data |
| Admin Keuangan | `admin@forsera.co.id` | `Forsera#2026` | Input & verifikasi transaksi (maker-checker), rekonsiliasi, insiden, laporan, master data (tanpa hapus) |
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
php artisan test               # seluruh test Pest (71 test)
php artisan migrate:fresh --seed
```

## Periode akuntansi (5 – 4, gajian tanggal 6)

Periode keuangan **tidak** memakai bulan kalender. Default periodenya **tanggal 5 s/d 4 bulan
berikutnya**, sehingga gajian tanggal 6 jatuh tepat setelah periode ditutup:

| Periode | Rentang tanggal |
| --- | --- |
| September 2026 | 5 Sep 2026 – 4 Okt 2026 |
| Oktober 2026 | 5 Okt 2026 – 4 Nov 2026 |

Label periode memakai bulan awal, jadi transaksi 3 November **masih** masuk periode Oktober 2026.
Tanggal mulai bisa diubah di **Pengaturan → Tanggal Mulai Periode** (1–28). Contoh bila diubah ke 25:
periode Oktober 2026 = 25 Sep 2026 – 24 Okt 2026.

Semua tampilan dan validasi memakai periode ini: filter “Periode berjalan/Periode sebelumnya” di
dashboard & laporan, default filter pemasukan/pengeluaran/rekonsiliasi, panel Perlu Perhatian, serta
penutupan periode (transaksi pada periode tertutup ditolak walau tanggalnya sudah masuk bulan
berikutnya). Logika terpusat di `app/Services/PeriodeAkuntansiService.php`.

## Identitas aplikasi (logo, nama, brand, title)

Owner dapat mengganti identitas aplikasi di **Pengaturan → Identitas aplikasi**:

| Kolom | Dipakai di |
| --- | --- |
| **Nama aplikasi** (`app_nama`) | Judul tab browser dan footer laporan PDF |
| **Brand / perusahaan** (`app_brand`) | Nama besar di sidebar, halaman login, hak cipta, header PDF |
| **Title / subjudul** (`app_title`) | Baris kecil di bawah brand (boleh kosong) |
| **Logo** (`app_logo`) | Ikon di sidebar, halaman login, dan favicon |

Logo berupa PNG/JPG/WEBP maksimal 2 MB (disarankan persegi). Berkas disimpan privat di
`storage/app/private/branding/` dan disajikan lewat route publik `/branding/logo` (halaman login juga
butuh logo). Tanpa logo, aplikasi memakai inisial brand (mis. “FG”). Bila logo dihapus, berkas lama
ikut dihapus. Setiap perubahan dicatat di Log Aktivitas.

## Komponen UI form

Form memakai komponen Blade sendiri supaya tampilannya seragam dan tetap ramah aksesibilitas:

| Komponen | Guna |
| --- | --- |
| `<x-pilihan>` | Dropdown pengganti `<select>` dengan pencarian di dalam panel |
| `<x-tanggal>` | Input tanggal dengan kalender popup dan format “22 Okt 2026” |
| `<x-password-input>` | Input password dengan tombol tampilkan/sembunyikan |
| `<x-file-upload>` | Dropzone lampiran transaksi |
| `<x-confirm-dialog>` | Dialog konfirmasi global untuk aksi berisiko |

Kolom pencarian `<x-pilihan>` muncul otomatis saat pilihan berjumlah 8 atau lebih, dan bisa
dipaksa selalu tampil lewat atribut `searchable`. Pencarian tidak peka huruf besar/kecil maupun
diakritik (mis. “cafe” tetap menemukan “Café”), menampilkan tombol bersihkan, serta menghormati
opsi yang disembunyikan skrip halaman (mis. kategori per unit usaha).

Semua komponen itu merender kontrol native di dalam markup, lalu `resources/js/app.js` menggantinya dengan
tampilan custom. Tanpa JavaScript, `<select>` dan `<input type="date">` tetap tampil dan berfungsi,
termasuk validasi `required` bawaan browser. Daftar opsi dropdown dibaca langsung dari `<option>`,
jadi tidak ada data yang diduplikasi di server maupun di JavaScript. Baris form dinamis (detail
pengeluaran) memanggil `window.siapkanKomponen(baris)` setelah hasil klon `<template>` dimasukkan.

## Indikator proses

Karena aplikasi berpindah halaman penuh, setiap pengambilan data, filter, dan submit form
menampilkan bilah progres tipis di puncak layar (`resources/js/app.js`). Tombol submit langsung
berstatus `aria-disabled`, diberi spinner, dan tidak bisa diklik lagi sampai halaman berikutnya
tampil — submit kedua (termasuk karena Enter ditekan berulang) diabaikan, sehingga transaksi tidak
tercatat dua kali. Tombol tetap `enabled` secara HTML agar pasangan `name`/`value`-nya tidak hilang
dari data form; penguncian memakai `pointer-events: none`.

Dialog konfirmasi tetap muncul lebih dulu: form baru dikunci setelah pengguna menyetujui, dan
membatalkan dialog tidak meninggalkan tombol dalam keadaan memuat. Tautan unduhan (Excel, PDF,
lampiran) ditandai `data-tanpa-memuat` agar tidak memicu bilah yang menggantung. Saat halaman
dipulihkan dari cache (tombol Back), semua status dibersihkan lewat event `pageshow`.

## Filter kolom tabel master

Enam tabel master (unit, metode, kategori, jenis, satuan, item) punya baris filter tepat di bawah
judul kolom. Nilainya dikirim sebagai parameter GET (`f_nama`, `f_tipe`, `f_status`, …) sehingga
hasil filter bisa di-bookmark dan tombol Back tetap bekerja:

- input teks menerapkan filter saat **Enter**, dan tombol ✕ mengosongkan satu kolom;
- dropdown langsung menyaring saat diganti (opsi “Semua …” mengosongkan kolom itu);
- hanya kolom yang terisi yang masuk ke URL, dan **Reset filter** muncul jika ada filter aktif;
- filter yang tidak dikenal diabaikan, dan nilainya hanya bisa menyentuh kolom yang didaftarkan
  pada peta `App\Support\FilterTabel` di masing-masing controller.

Menambah filter kolom baru berarti menambah satu entri pada peta controller, lalu memakai
`<x-filter-teks>` atau `<x-filter-pilih>` di baris `filter-baris` pada view. Form GET-nya sengaja
diletakkan di luar `<table>` (input dihubungkan lewat atribut `form`) karena di dalam tabel sudah
ada form hapus POST yang tidak boleh bersarang.

## Alur verifikasi pemasukan (status “Belum Dicek”)

“Belum Dicek” bukan tombol approve yang terlewat — artinya transaksi sudah dicatat tetapi **belum
dicocokkan dengan uang yang benar-benar diterima**. Pencocokan dilakukan lewat rekonsiliasi:

1. Staf input pemasukan (kas/QRIS/transfer) → status **Belum Dicek**.
2. **Owner** atau **Admin Keuangan** membuka **Rekonsiliasi → Rekonsiliasi baru** (atau klik tombol
   **Verifikasi** pada baris pemasukan, sudah terisi unit/metode/tanggal).
3. Pilih transaksi dalam rentang, isi **Total diterima** (dana yang masuk ke kas/rekening), biaya
   admin/MDR, dan potongan kas bila ada, lalu simpan.
4. Hasilnya otomatis: **Sesuai** (selisih ≤ toleransi Rp 1.000), **Selisih** (insiden dibuat otomatis),
   atau **Menunggu Settlement** (dana belum masuk, tanpa insiden). Insiden ditindaklanjuti sampai
   transaksi menjadi **Selesai**.
5. Di daftar pemasukan tampil kode rekonsiliasi dan **nama pemeriksa** (“REK-… · dicek <nama>”).

**Maker-checker:** Admin Keuangan tidak bisa mencocokkan transaksi yang ia buat sendiri; hanya Owner
yang dikecualikan. Staf unit tidak punya akses ke menu Rekonsiliasi sama sekali.

## Aturan bisnis inti

- **Maker-checker**: staf dan admin tidak boleh memverifikasi transaksi yang mereka buat sendiri;
  hanya Owner yang bebas. Verifikasi mengubah status pemasukan menjadi `Terverifikasi`.
- **Pengeluaran**: total dihitung di server dari detail item; nominal di atas batas persetujuan
  otomatis berstatus menunggu persetujuan Owner.
- **Batas transaksi & batas persetujuan** diatur di menu Pengaturan, bukan di kode.
- **Periode terkunci**: transaksi yang jatuh pada periode akuntansi (5–4) yang sudah ditutup tidak
  bisa diubah tanpa izin koreksi khusus, dan setiap koreksi tercatat di log aktivitas.
- **Rekonsiliasi**: selisih di dalam toleransi dianggap sesuai; selisih di luar toleransi otomatis
  membuat insiden dengan kode `INC-YYYYMM-####`.
- **Penyelesaian insiden**: setoran susulan, koreksi pencatatan, atau write-off (hanya Owner).
- **Lampiran** bukti transaksi disimpan di disk privat dan diunduh lewat route terautorisasi.
- **Hapus data (Owner)**: hanya Owner yang bisa menghapus transaksi dan master data, baik per baris
  maupun lewat ceklist **Hapus terpilih**. Master data yang sudah dipakai transaksi/kategori tidak
  bisa dihapus (nonaktifkan saja); transaksi terkunci atau yang sudah direkonsiliasi dilewati otomatis
  dan alasannya ditampilkan.

Detail teknis: [docs/PLAN.md](docs/PLAN.md). Tupoksi dua finance, alur setoran kas, kas kecil,
pengadaan, analisa penjualan, fee franchise 5%, dan gajian tanggal 6: [docs/TUPOKSI_DAN_ALUR_KEUANGAN.md](docs/TUPOKSI_DAN_ALUR_KEUANGAN.md).
