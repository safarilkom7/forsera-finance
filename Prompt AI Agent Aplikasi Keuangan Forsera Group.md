# Prompt AI Agent: Aplikasi Keuangan Forsera Group

*Brief lengkap untuk AI agent (Claude Code, Cursor, Codex, dll.). Salin seluruh isi dokumen ini sebagai prompt pertama.*

## 1. Peran dan tujuan

Anda adalah senior full-stack engineer sekaligus analis sistem keuangan UMKM. Bangun **aplikasi web pencatatan dan monitoring keuangan** untuk tiga unit usaha milik satu owner: pencatatan pemasukan dan pengeluaran, verifikasi (cross-check) uang yang benar-benar masuk, penanganan insiden selisih, dan dashboard monitoring.

Hasil akhir harus **siap dipakai di produksi**, bukan prototipe: berjalan, teruji, terdokumentasi, tanpa TODO, placeholder, atau data palsu di kode produksi.

## 2. Konteks bisnis

Tiga unit usaha:

1. **FORSERA RESTO**: menjual makanan.
2. **KOPI AROBI**: menjual kopi (franchise, kemungkinan ada royalti/fee ke franchisor).
3. **FORSERA BARBERSHOP**: jasa cukur rambut, menjual pomade dan parfum.

Masalah utama: owner harus bisa memastikan setiap rupiah yang dicatat staf benar-benar masuk (QRIS, transfer, maupun tunai), dan memantau kondisi keuangan ketiga usaha dari satu dashboard. Pengguna: owner (sering lewat HP), admin keuangan, dan staf tiap unit.

Lokalisasi: UI Bahasa Indonesia, mata uang Rupiah (`Rp 1.250.000`), zona waktu `Asia/Jakarta`, tanggal `20 Okt 2026`.

## 3. Aturan kerja untuk agent

1. Baca seluruh brief sebelum menulis kode. Buat `docs/PLAN.md` (arsitektur, daftar migrasi, service, urutan milestone), lalu langsung kerjakan.
2. Kerjakan per milestone (bagian 15). Setelah tiap milestone: `php artisan migrate:fresh --seed` harus sukses, semua test hijau, lint bersih, lalu commit dengan pesan yang jelas.
3. Jangan berhenti untuk bertanya kecuali ada blocker nyata (misalnya lingkungan tidak bisa dijalankan). Untuk hal yang ambigu, pilih opsi paling masuk akal, catat di `docs/ASUMSI.md`, dan lanjut.
4. Jangan menambah fitur di luar cakupan (bagian 17) dan jangan mengubah aturan bisnis di bagian 7 tanpa alasan kuat yang dicatat di `docs/ASUMSI.md`.
5. Logika bisnis berada di service class (`RekonsiliasiService`, `InsidenService`, `PeriodeService`, `DashboardService`, dll.), bukan di controller atau view. Operasi multi-langkah memakai DB transaction. Gunakan PHP backed enum untuk semua status. Aktifkan `Model::preventLazyLoading()` di non-production dan pastikan tidak ada N+1.
6. Semua validasi dilakukan di server; UI hanya membantu. Pesan error dalam Bahasa Indonesia yang jelas.

## 4. Tech stack

*Stack ditetapkan: Laravel 11 + React + PrimeReact. Seluruh aturan bisnis di bagian 7 tidak bergantung pada pilihan stack.*

- PHP 8.3+, **Laravel 11**, MySQL 8 (atau PostgreSQL); kolom `json` untuk lampiran.
- **Inertia.js + React 18 + TypeScript** (monolith, tanpa API terpisah), dari starter kit Laravel Breeze (Inertia, React, TypeScript), dibangun dengan Vite.
- **PrimeReact** (versi stabil terbaru) sebagai UI kit: `DataTable`, `Calendar`, `InputNumber`, `Dropdown`, `SelectButton`, `Steps`, `Dialog`, `ConfirmDialog`, `FileUpload`, `Timeline`, `Toast`, `Tag`, dan `Chart` (Chart.js). Tema Lara (light dan dark) beserta `primeicons`.
- **Catatan:** PrimeVue hanya untuk Vue dan tidak boleh dipakai di proyek React ini. Bila ingin Vue, seluruh kombinasi diganti menjadi Inertia Vue + PrimeVue; aturan bisnis tetap sama.
- Paket Laravel: `spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-backup`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`. Frontend tambahan: `dayjs`.
- Kualitas: Pest, Laravel Pint, Larastan (level 6+); ESLint, Prettier, TypeScript strict, Vitest dan Testing Library untuk komponen kunci.
- Uang disimpan sebagai **integer rupiah** (`unsignedBigInteger`, tanpa sen); jumlah barang `decimal(12,3)`. Dilarang memakai float untuk uang.
- File lampiran di disk privat dan diakses lewat route yang terotorisasi.

**Arsitektur dan konvensi**

1. Alur backend: controller tipis → FormRequest (validasi) → service → model. Data dikirim ke halaman lewat Inertia props memakai API Resource, bukan model mentah.
2. Halaman React di `resources/js/Pages/<Fitur>/` (Index, Create, Edit, Show), komponen bersama di `resources/js/Components/`; semua props bertipe TypeScript.
3. `DataTable` PrimeReact memakai mode lazy: pagination, sorting, filter, dan pencarian dijalankan di server lewat query string (`router.get` dengan `preserveState`). Jangan memuat seluruh data ke browser.
4. Form memakai `useForm` Inertia; error validasi server dipetakan ke tiap field PrimeReact. Lampiran diunggah lewat endpoint terpisah yang mengembalikan metadata json, dan job terjadwal membersihkan file yang tidak pernah terhubung ke transaksi.
5. Shared props Inertia: pengguna, peran, izin, unit yang boleh diakses, pengaturan yang relevan, dan flash message. Menu dan tombol disembunyikan sesuai izin, tetapi otorisasi yang sebenarnya selalu di server (Policy).
6. Komponen bersama wajib: `RupiahInput` (InputNumber locale id-ID, IDR, tanpa desimal), `StatusBadge`, `DataTableServer`, `UnitFilter`, `PeriodeFilter`, `FileUploader` (kamera/galeri, preview, maksimal 5 file), `SelisihPreview`, `ConfirmAction`.
7. Dashboard: data dari `DashboardService` lewat Inertia props atau endpoint JSON, grafik memakai `Chart` PrimeReact, filter periode dan unit tersinkron dengan URL.
8. Notifikasi in-app memakai database notifications Laravel dengan polling ringan (sekitar 60 detik); tanpa WebSocket.
9. Ekspor Excel/PDF dibuat di server dan diunduh lewat route terotorisasi.
10. Karena tidak memakai panel admin siap pakai, semua halaman di bagian 8 (termasuk pengguna dan peran, pengaturan, log aktivitas, dan notifikasi) dibangun sendiri dengan komponen yang konsisten.

## 5. Pengguna dan hak akses

| Peran | Hak |
| --- | --- |
| Owner | Akses penuh: semua unit, verifikasi, write-off, koreksi data terkunci, tutup/buka periode, kelola pengguna dan pengaturan |
| Admin Keuangan | Semua unit: input, verifikasi/rekonsiliasi, kelola insiden, laporan, master data. Tidak bisa write-off, koreksi data terkunci, atau buka periode |
| Staf Unit | Hanya unit yang ditugaskan: input pemasukan/pengeluaran, edit/hapus miliknya selama status BELUM\_DICEK dan periode belum ditutup. Tidak melihat laba, rekonsiliasi, atau unit lain |

Aturan tambahan:

- **Maker-checker:** pengguna selain Owner tidak boleh memverifikasi rekonsiliasi yang memuat pemasukan buatannya sendiri.
- Satu Staf dapat ditugaskan ke lebih dari satu unit (tabel pivot `user_unit_usaha`).
- Wajib ganti password pada login pertama; seed satu akun Owner awal dengan password sementara yang dicantumkan di README.

## 6. Skema database

Gunakan migrasi Laravel, foreign key, index, dan soft delete untuk tabel transaksi.

```
-- MASTER
unit_usaha          id, kode (unik), nama, tipe, aktif
metode_pembayaran   id, nama, tipe (TUNAI|QRIS|TRANSFER), aktif
kategori_pemasukan  id, unit_usaha_id (null = semua unit), nama, aktif
jenis_pengeluaran   id, nama, aktif
satuan              id, nama, singkatan
item                id, nama (unik), satuan_default_id, jenis_pengeluaran_id (null),
                    harga_terakhir (null), aktif
pengaturan          key, value (bertipe), deskripsi

-- TRANSAKSI
pemasukan
  id, tanggal (index), unit_usaha_id, kategori_pemasukan_id, metode_pembayaran_id,
  nominal (>0), note, lampiran (json array, default []),
  status_verifikasi (BELUM_DICEK|MENUNGGU_SETTLEMENT|SESUAI|SELISIH|SELESAI),
  rekonsiliasi_id (null), dibuat_oleh, diubah_oleh, timestamps, deleted_at
  index: (unit_usaha_id, tanggal), (metode_pembayaran_id, status_verifikasi)

pengeluaran
  id, tanggal, unit_usaha_id, jenis_pengeluaran_id, metode_pembayaran_id,
  total_nominal, dari_kas_harian (bool), note, lampiran (json),
  status_persetujuan (DISETUJUI|MENUNGGU|DITOLAK), disetujui_oleh, disetujui_at,
  rekonsiliasi_id (null), dibuat_oleh, diubah_oleh, timestamps, deleted_at

detail_pengeluaran
  id, pengeluaran_id (cascade), item_id, jumlah decimal(12,3), satuan_id,
  harga_satuan, subtotal

-- VERIFIKASI DAN INSIDEN
rekonsiliasi
  id, kode (REK-YYYYMM-0001), unit_usaha_id, metode_pembayaran_id,
  periode_mulai, periode_selesai, tanggal_diterima,
  total_dicatat, potongan_kas (default 0), biaya_admin (default 0),
  total_diterima, selisih (signed),
  status (MENUNGGU_SETTLEMENT|SESUAI|SELISIH|SELESAI|DIBATALKAN),
  catatan, bukti (json), diverifikasi_oleh, diverifikasi_at

insiden
  id, kode (INC-YYYYMM-0001), rekonsiliasi_id, jenis, arah (KURANG|LEBIH),
  nominal_selisih (selalu positif), nominal_terselesaikan (default 0),
  status (OPEN|INVESTIGASI|SELESAI|DIHAPUSKAN|DIBATALKAN),
  penyebab, tipe_penyelesaian (null), catatan_penyelesaian,
  penanggung_jawab_id (null), lampiran (json), dibuat_oleh, ditutup_oleh, ditutup_at

insiden_log   id, insiden_id, user_id, aksi, catatan, nominal (null), lampiran (json), created_at
periode_tutup id, tahun, bulan, unit_usaha_id (null = semua), ditutup_oleh, ditutup_at,
              dibuka_kembali_oleh, dibuka_kembali_at, alasan

-- SISTEM
users, roles/permissions (spatie), user_unit_usaha, activity_log, notifications
```

Enum `insiden.jenis`: KURANG\_SETOR, LEBIH\_SETOR, SALAH\_INPUT, PEMBAYARAN\_DOBEL, SELISIH\_BIAYA\_ADMIN, DANA\_BELUM\_CAIR, LAINNYA.

Enum `tipe_penyelesaian`: KOREKSI\_DATA, SETORAN\_SUSULAN, DANA\_CAIR\_SUSULAN, GANTI\_RUGI, CATAT\_PENDAPATAN\_LAIN, WRITE\_OFF.

Key `pengaturan` beserta default: `toleransi_selisih` = 1000; `batas_hari_verifikasi` = 2; `batas_hari_settlement` = 3; `mdr_qris_persen` = 0.7 (hanya saran isian awal); `batas_persetujuan_pengeluaran` = 0 (0 = nonaktif); `batas_lampiran_pengeluaran` = 50000 (hanya memunculkan peringatan, tidak memblokir); `lonjakan_harga_persen` = 20; `nominal_maks_transaksi` = 2000000000.

## 7. Aturan bisnis (wajib diikuti persis)

**Pemasukan**

1. Tanggal tidak boleh di masa depan. Nominal > 0 dan ≤ `nominal_maks_transaksi`. Kategori harus milik unit yang dipilih (atau berlaku untuk semua unit). Unit dan metode harus aktif.
2. Status awal selalu BELUM\_DICEK. Lampiran: maksimal 5 file, jpg/png/webp/pdf, maksimal 5 MB per file, disimpan sebagai json array `[{path, nama_asli, mime, ukuran, diunggah_oleh, diunggah_at}]`.
3. Pemasukan berstatus selain BELUM\_DICEK **terkunci**: tidak bisa diubah atau dihapus dari UI biasa.

**Pengeluaran**

4. Bila ada detail: `subtotal = round(jumlah × harga_satuan)` dan `total_nominal = sum(subtotal)` (dihitung server, read-only). Tanpa detail: `total_nominal` diisi manual (untuk listrik, gaji, royalti, dll.).
5. `dari_kas_harian` default true bila metode TUNAI, false selain itu.
6. Setiap simpan item bernilai memperbarui `item.harga_terakhir`. Bila `harga_satuan` melebihi rata-rata 5 pembelian terakhir item itu lebih dari `lonjakan_harga_persen`, tampilkan peringatan dan masukkan ke panel Perlu Perhatian (tidak memblokir).
7. Bila `batas_persetujuan_pengeluaran` > 0 dan nominal melebihi batas serta pembuatnya Staf, status menjadi MENUNGGU sampai Owner menyetujui atau menolak. Pengeluaran MENUNGGU/DITOLAK tidak dihitung di dashboard dan laporan.

**Rekonsiliasi (cross-check ke owner)**

8. Dilakukan per kombinasi unit usaha × metode pembayaran × rentang tanggal, bukan per transaksi (QRIS cair per batch dan dipotong biaya; transfer dan tunai masuk secara gabungan).
9. Alur: pilih unit, metode, rentang tanggal → sistem menampilkan pemasukan BELUM\_DICEK/MENUNGGU\_SETTLEMENT di rentang itu (semua tercentang, boleh dikeluarkan satu per satu) → isi `total_diterima`, `biaya_admin`, `tanggal_diterima`, bukti (json), catatan → preview selisih langsung → simpan.
10. `total_dicatat` = jumlah pemasukan terpilih. Khusus TUNAI, `potongan_kas` = jumlah pengeluaran TUNAI dengan `dari_kas_harian` = true, unit sama, di rentang tanggal, status DISETUJUI, dan belum terhubung ke rekonsiliasi (otomatis; Owner dapat mengoreksi dengan catatan wajib). Untuk non-tunai, `potongan_kas` = 0.
11. **Rumus:** `selisih = total_diterima − (total_dicatat − biaya_admin − potongan_kas)`. Negatif = KURANG, positif = LEBIH. Untuk QRIS, isian awal `biaya_admin` disarankan `round(total_dicatat × mdr_qris_persen / 100)` namun bisa diubah.
12. Hasil: `|selisih| ≤ toleransi_selisih` → status SESUAI (selisih bukan nol dicatat otomatis sebagai pembulatan). `|selisih| > toleransi_selisih` → status SELISIH dan **insiden dibuat otomatis**. Pemasukan terpilih ikut berstatus SESUAI/SELISIH dan terhubung ke rekonsiliasi (`rekonsiliasi_id`), begitu pula pengeluaran kas yang dipotong.
13. Opsi “Tandai menunggu settlement” untuk QRIS/transfer yang dananya belum masuk: pemasukan menjadi MENUNGGU\_SETTLEMENT tanpa insiden. Yang melewati `batas_hari_settlement` masuk panel Perlu Perhatian.
14. Rekonsiliasi hanya bisa dibatalkan oleh Owner dengan alasan wajib: pemasukan dan pengeluaran terkait kembali ke kondisi awal, insiden terkait berstatus DIBATALKAN. Semua tercatat di audit log.
15. Maker-checker berlaku (bagian 5).

**Insiden**

16. Insiden otomatis berstatus OPEN, penanggung jawab opsional. Alur: OPEN → INVESTIGASI → SELESAI atau DIHAPUSKAN. Setiap perubahan, komentar, dan bukti dicatat di `insiden_log` (timeline).
17. Penutupan wajib mengisi `penyebab`, `tipe_penyelesaian`, dan `catatan_penyelesaian`:
    - **KOREKSI\_DATA:** Owner mengoreksi pemasukan terkait (aturan 20); rekonsiliasi dihitung ulang otomatis; bila selisih menjadi ≤ toleransi, insiden SELESAI otomatis.
    - **SETORAN\_SUSULAN, DANA\_CAIR\_SUSULAN, GANTI\_RUGI:** catat nominal yang diterima di timeline (menambah `nominal_terselesaikan`, boleh bertahap); bisa ditutup bila `nominal_terselesaikan ≥ nominal_selisih`.
    - **CATAT\_PENDAPATAN\_LAIN** (hanya arah LEBIH): sistem membuat pemasukan otomatis berkategori “Pendapatan Lain-lain”, metode sama, sebesar selisih, status SELESAI, terhubung ke insiden.
    - **WRITE\_OFF:** hanya Owner, alasan wajib; sisa selisih dihapuskan, status DIHAPUSKAN, dan muncul di laporan sebagai “Selisih dihapuskan”.
18. Saat insiden SELESAI atau DIHAPUSKAN, status rekonsiliasi dan pemasukan terkait menjadi SELESAI.

**Penguncian data dan periode**

19. Tutup periode per bulan (semua unit atau per unit) oleh Owner. Transaksi, rekonsiliasi, dan koreksi yang tanggalnya jatuh di periode tertutup ditolak untuk semua peran. Owner dapat membuka kembali dengan alasan wajib.
20. **Koreksi** data terkunci: hanya Owner, alasan wajib, tidak di periode tertutup. Sistem menyimpan nilai lama dan baru di audit log, membuka kembali rekonsiliasi terkait, dan menghitung ulang selisihnya.
21. Hapus = soft delete, hanya untuk data BELUM\_DICEK.

**Audit**

22. Seluruh pembuatan, perubahan, penghapusan, perubahan status, perubahan pengaturan, login, dan unduhan lampiran dicatat (siapa, kapan, IP, nilai lama/baru) lewat activity log, dan tidak bisa diedit dari UI.

## 8. Halaman dan fitur

- **Dashboard** (bagian 9).
- **Pemasukan:** tabel dengan filter tanggal, unit, metode, kategori, status, pembuat; pencarian note; total di footer; ekspor. Form cepat mobile-first (bagian 12).
- **Pengeluaran:** sama seperti di atas, plus repeater detail item, buat item baru langsung dari form, dan isi otomatis satuan serta harga terakhir.
- **Rekonsiliasi:** daftar dan wizard 3 langkah (aturan 9), badge status berwarna, halaman detail yang menampilkan daftar pemasukan, perhitungan selisih, bukti, dan insiden.
- **Insiden:** daftar, halaman detail dengan timeline, aksi ganti status/penanggung jawab/penutupan sesuai aturan 16–18.
- **Laporan** (bagian 10).
- **Master data:** unit usaha, metode, kategori pemasukan, jenis pengeluaran, satuan, item (dengan riwayat harga). Nonaktifkan, jangan hapus, bila sudah dipakai.
- **Pengaturan:** semua key di bagian 6.
- **Pengguna dan peran, Log aktivitas (baca saja), Tutup periode.**
- **Notifikasi in-app** (ikon lonceng) untuk: insiden baru, pemasukan belum diverifikasi melewati batas, rekonsiliasi menunggu settlement melewati batas, pengeluaran menunggu persetujuan.

## 9. Dashboard

Filter global: periode (Hari ini, 7 hari, Bulan ini, Bulan lalu, Tahun ini, Custom) dan unit (Semua atau satu unit). Default: Bulan ini, Semua.

Owner dan Admin melihat:

1. **Kartu ringkasan:** Total Pemasukan dan Total Pengeluaran (masing-masing dengan persentase perubahan terhadap periode sebelumnya yang setara), Laba Bersih (= pemasukan − pengeluaran) beserta margin %, Terverifikasi (% nominal pemasukan berstatus SESUAI/SELISIH/SELESAI), dan Selisih Terbuka (jumlah insiden OPEN/INVESTIGASI beserta total nominalnya).
2. **Grafik:** tren harian pemasukan vs pengeluaran; pemasukan per unit (stacked); komposisi per metode pembayaran; 5 teratas jenis pengeluaran.
3. **Tabel ringkasan per unit:** pemasukan, pengeluaran, laba, margin, % terverifikasi.
4. **Panel Perlu Perhatian**, masing-masing dengan tautan langsung ke halaman terkait: pemasukan BELUM\_DICEK lebih lama dari `batas_hari_verifikasi` (dikelompokkan per unit dan metode, tombol “Verifikasi sekarang”); insiden terbuka; rekonsiliasi menunggu settlement melewati batas; pengeluaran ≥ `batas_lampiran_pengeluaran` tanpa lampiran; lonjakan harga item; pengeluaran menunggu persetujuan. Tampilkan juga “Selisih dihapuskan” pada periode terpilih.

Staf hanya melihat ringkasan input hari ini untuk unitnya (jumlah dan total yang ia input beserta status verifikasinya), tanpa laba dan tanpa unit lain.

Semua angka dihitung dengan query teragregasi (bukan loop) dan cache singkat (maksimal 60 detik) yang di-invalidate saat data berubah. Data soft-deleted, MENUNGGU/DITOLAK, dan DIBATALKAN tidak dihitung.

## 10. Laporan (ekspor Excel dan PDF)

1. Laba rugi sederhana per unit dan gabungan, per periode.
2. Pemasukan per unit × kategori × metode.
3. Pengeluaran per jenis dan per item (jumlah, harga rata-rata, harga terendah dan tertinggi).
4. Rekonsiliasi: ringkasan selisih per metode dan per unit, termasuk daftar insiden dan hasil penyelesaiannya (termasuk selisih dihapuskan).
5. Buku kas harian per unit (urut tanggal: pemasukan dan pengeluaran dengan saldo berjalan relatif terhadap periode).

Semua laporan menghormati filter periode/unit dan hak akses peran.

## 11. Keamanan, audit, performa

- Otorisasi lewat Policy/Gate di server pada setiap route dan query (bukan sekadar menyembunyikan menu di UI); Staf tidak boleh punya akses lintas unit (wajib diuji). Cegah IDOR pada unduhan lampiran.
- Validasi mime dan ukuran file di server, nama file acak, disk privat.
- Rate limit login, kebijakan password kuat, dukungan 2FA untuk Owner bila tersedia.
- Index sesuai pola query, pagination, eager loading.
- Jadwalkan backup harian database dan lampiran (`spatie/laravel-backup`) serta dokumentasikan cara restore.
- `.env.example` lengkap; tidak ada secret di repository.

## 12. UI/UX

- **Mobile-first:** semua form dan tabel nyaman di layar HP, karena owner akan banyak memakai HP.
- Form pemasukan cepat: tanggal default hari ini; unit default (Staf: unitnya, Admin/Owner: yang terakhir dipakai); metode berupa tombol pilihan (3 opsi); kolom nominal numerik dengan pemisah ribuan; kategori terfilter otomatis sesuai unit; tombol “Simpan dan tambah lagi”; unggah lampiran langsung dari kamera.
- Badge status: BELUM\_DICEK abu-abu, MENUNGGU\_SETTLEMENT kuning, SESUAI hijau, SELISIH merah, SELESAI biru. Selisih KURANG merah, LEBIH oranye.
- Konfirmasi untuk aksi destruktif, pesan sukses/gagal yang jelas, empty state yang informatif, mode gelap, loading state.
- Tabel: header menempel, kolom dapat disembunyikan, filter tersimpan.

## 13. Data awal (seeder)

- **Seeder produksi:** 3 unit usaha (FRS, KAR bertipe KOPI\_FRANCHISE, FBS); metode (TUNAI, QRIS, TRANSFER); peran dan izin; pengaturan default; satuan umum (pcs, kg, gram, liter, ml, pack, box, botol, porsi, jam, bulan, unit); jenis pengeluaran (Bahan Baku, Gaji dan Upah, Listrik/Air/Internet, Sewa, Peralatan, Pemasaran, Royalti/Fee Franchise, Perawatan dan Perbaikan, Transport, Lain-lain); kategori pemasukan (Resto: Dine-in, Take away, Online; Kopi Arobi: Kopi, Non-kopi; Barbershop: Jasa Cukur, Pomade, Parfum; semua unit: Pendapatan Lain-lain); 1 akun Owner.
- **`DemoSeeder`** terpisah (hanya local/staging): data realistis 3 bulan untuk ketiga unit, mencakup pemasukan di semua metode; rekonsiliasi berstatus SESUAI, SELISIH KURANG, SELISIH LEBIH, dan MENUNGGU\_SETTLEMENT; insiden di berbagai status; satu periode tertutup; serta akun contoh untuk tiap peran.

## 14. Pengujian (Pest)

Wajib ada test untuk:

- Rumus selisih (KURANG, LEBIH, pas, dalam toleransi, dengan `biaya_admin`, dengan `potongan_kas` pada TUNAI).
- Insiden otomatis hanya dibuat bila selisih di atas toleransi.
- Semua tipe penyelesaian insiden, termasuk penyelesaian bertahap, dan write-off hanya oleh Owner.
- Penguncian pemasukan, koreksi oleh Owner, hitung ulang rekonsiliasi, pembatalan rekonsiliasi.
- Tutup periode memblokir create/update/delete/koreksi untuk semua peran.
- Total pengeluaran dari detail, pembulatan, update `harga_terakhir`, peringatan lonjakan harga.
- Otorisasi per peran: Staf tidak dapat melihat atau mengubah data unit lain; maker-checker; IDOR lampiran.
- Validasi (tanggal masa depan, nominal, kategori beda unit, file tidak valid).
- Query dashboard: angka benar terhadap dataset kecil yang diketahui, serta pengecualian data soft-deleted, DIBATALKAN, dan MENUNGGU.

Frontend: test komponen kunci dengan Vitest dan Testing Library (RupiahInput, preview selisih pada wizard rekonsiliasi, StatusBadge), dengan TypeScript strict aktif.

Target: semua test hijau, Larastan, Pint, ESLint, dan tsc --noEmit bersih.

## 15. Tahapan pengerjaan

- **M0 Fondasi:** proyek Laravel 11 (Breeze: Inertia + React + TypeScript), PrimeReact dan tema, layout responsif (sidebar, topbar), komponen bersama (RupiahInput, StatusBadge, DataTableServer, FileUploader), auth, peran/izin, lokalisasi, pengaturan, activity log. *Selesai bila* login per peran berfungsi dan `migrate:fresh --seed` sukses.
- **M1 Master dan pengguna:** CRUD master, pengguna, penugasan unit, riwayat harga item.
- **M2 Transaksi:** pemasukan dan pengeluaran lengkap (detail item, lampiran json, validasi, pembatasan peran, penguncian, tutup periode, persetujuan, peringatan harga). *Selesai bila* aturan 1–7, 19, 21, 22 lulus test.
- **M3 Rekonsiliasi dan insiden:** wizard, rumus, toleransi, insiden otomatis, timeline, semua tipe penyelesaian, pembatalan, koreksi, notifikasi. *Selesai bila* aturan 8–18 dan 20 lulus test.
- **M4 Dashboard dan laporan:** seluruh bagian 9 dan 10, ekspor Excel/PDF.
- **M5 Hardening:** performa, audit lengkap, backup, DemoSeeder, dokumentasi, rapikan UX, uji akhir menyeluruh.
- **M6 (opsional, bila semua di atas selesai): Tutup Kasir Harian.** Staf mengisi uang fisik di laci per unit per hari (tabel `tutup_kasir`: tanggal, unit, total\_tunai\_dicatat, pengeluaran\_kas, kas\_fisik, selisih, catatan), dipakai sebagai isian awal `total_diterima` pada rekonsiliasi TUNAI.

## 16. Deliverable

- Kode lengkap: migrasi, model, enum, service, policy, controller, form request, halaman Inertia React (TSX), komponen UI, seeder, factory, dan test.
- `README.md` (Bahasa Indonesia): prasyarat, instalasi langkah demi langkah, konfigurasi `.env`, cara menjalankan, akun awal, menjalankan test, backup/restore, deploy singkat.
- `docs/PLAN.md`, `docs/ASUMSI.md`, `docs/ATURAN_BISNIS.md` (ringkasan bagian 7 sesuai implementasi), `docs/PANDUAN_PENGGUNA.md` (alur harian staf, alur verifikasi owner, cara menangani insiden).
- Opsional: `docker-compose.yml` atau Laravel Sail agar mudah dijalankan.

## 17. Di luar cakupan (jangan dibangun)

Integrasi POS/QRIS otomatis, impor mutasi bank otomatis, payroll, manajemen stok, pajak, multi-mata-uang, aplikasi native, saldo antar-rekening. Tulis sebagai saran lanjutan di akhir README, jangan diimplementasikan.

## 18. Definition of done

Aplikasi berjalan dari nol hanya dengan mengikuti README; seluruh aturan bagian 7 terimplementasi dan teruji; semua test hijau; tidak ada TODO, placeholder, atau data dummy di kode produksi; dashboard menampilkan angka yang konsisten dengan laporan; dan satu siklus penuh berhasil diuji manual (input pemasukan QRIS → rekonsiliasi dengan selisih → insiden → penyelesaian → dashboard dan laporan sesuai).

Di akhir pekerjaan, berikan ringkasan: apa yang selesai, asumsi yang dibuat, dan saran lanjutan.
