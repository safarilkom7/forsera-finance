# Tupoksi Finance & Alur Uang Forsera Group

Dokumen ini menjawab tujuh pertanyaan operasional keuangan Forsera Group dan memetakannya ke
kemampuan aplikasi saat ini. Kolom status di bagian akhir memakai penanda:

- ✅ **Ada** — sudah bisa dijalankan di aplikasi sekarang.
- ⚠️ **Sebagian** — ada kolom/entitasnya, tetapi alurnya masih manual atau belum lengkap.
- ❌ **Belum** — perlu pengembangan baru.

Asumsi yang dipakai (koreksi bila berbeda):

- 2 orang finance, 2 kasir unit, 1 rekening penampung **Forsera House**.
- QRIS masuk langsung ke rekening Forsera House (settlement H+1/H+2, dipotong MDR ±0,7%).
- Uang tunai dipegang kasir per shift, lalu diserahkan ke finance, lalu finance setor ke bank.
- Unit yang dikelola: Forsera Resto, Kopi Arobi (franchise), Forsera Barbershop.
- Gajian tanggal 6, periode akuntansi aplikasi 5 s/d 4 (sudah menjadi default aplikasi).

---

## 1. Peta alur uang

```
                    ┌──────────────── QRIS ────────────────┐
Pelanggan ── bayar ─┤                                      ├─► Rekening Forsera House
                    └──── Tunai ──► Kasir (2 orang) ──► Finance ──► Setoran bank
                                                        │
                                                        ├─► Kas kecil (petty cash)
                                                        └─► Pengeluaran operasional
```

Tiga hal yang harus selalu jelas untuk setiap rupiah:

1. **Siapa yang memegang uangnya** (kasir, finance, bank).
2. **Di mana uang itu dicatat** (pemasukan, pengeluaran, setoran, kas kecil).
3. **Siapa yang memeriksa** catatan itu terhadap uang fisik/mutasi rekening.

---

## 2. Pertanyaan 1 — Tupoksi masing-masing finance

### 2.1 Prinsip pembagian

Dengan hanya dua orang, pembagian tugas **tidak boleh** berdasarkan "yang penting terbagi rata",
tetapi berdasarkan **pemisahan fungsi**:

- **Pencatat ≠ pemeriksa.** Orang yang menginput transaksi tidak boleh menjadi orang yang
  memverifikasi transaksi itu. Aplikasi sudah menegakkan ini (maker-checker): Admin Keuangan tidak
  bisa merekonsiliasi pemasukan yang ia buat sendiri, hanya Owner yang dikecualikan.
- **Pemegang uang ≠ pencatat beban.** Pemegang kas kecil tidak boleh menjadi satu-satunya yang
  mencatat pemakaiannya tanpa bukti.
- **Penerima setoran ≠ penyetor.** Kasir menyerahkan ke Finance A; Finance B yang mencocokkan
  mutasi rekening.

Karena itu pembagian yang disarankan adalah **per fungsi**, bukan per unit: satu orang menguasai
sisi **uang masuk & kas**, satu orang menguasai sisi **uang keluar & bank**. Keduanya saling
memeriksa.

### 2.2 Finance A — Kas & Pemasukan (*Cash & Sales Controller*)

**Tujuan jabatan:** memastikan seluruh uang tunai yang diterima kasir benar-benar masuk ke bank,
dan seluruh pemasukan tercatat lengkap serta cocok dengan kas fisik.

| Tanggung jawab | Rincian | Frekuensi |
| --- | --- | --- |
| Tutup kas harian | Hitung kas fisik bersama kasir, bandingkan dengan rekap penjualan tunai, catat selisih | Harian, setelah shift tutup |
| Serah terima kasir | Tanda tangan berita acara serah terima kas per kasir, simpan di map bernomor | Harian |
| Setoran bank | Setor tunai ke rekening Forsera House, unggah slip setoran | Harian, H+1 sebelum jam cut-off |
| Rekonsiliasi tunai | Cocokkan total pemasukan tunai − pengeluaran dari kas − kas di tangan = nominal setor | Harian/maksimal 3 hari |
| Verifikasi QRIS | Cocokkan nominal QRIS dengan settlement yang masuk rekening, catat MDR, tandai yang belum settlement | Harian |
| Kas kecil | Menjadi pemegang kas kecil, catat pemakaian + bukti, ajukan pengisian ulang | Harian |
| Input pemasukan | Memastikan pemasukan dari kasir sudah diinput (boleh dibantu staf unit) | Harian |
| Serah terima ke Finance B | Menyerahkan rekap kas harian + slip setoran untuk diperiksa | Harian |

### 2.3 Finance B — Pengeluaran & Rekonsiliasi Bank (*Disbursement & Bank Controller*)

**Tujuan jabatan:** memastikan setiap rupiah yang keluar didukung bukti, disetujui sesuai batas,
tercatat pada jenis yang benar, dan rekening bank cocok dengan pembukuan.

| Tanggung jawab | Rincian | Frekuensi |
| --- | --- | --- |
| Verifikasi pengeluaran | Periksa nota/invoice, detail item, harga, penerima, kesesuaian anggaran; tolak yang tidak lengkap | Harian |
| Eksekusi pembayaran | Transfer tunai/bank sesuai pengeluaran yang sudah disetujui, simpan bukti transfer | Harian |
| Pengadaan | Memverifikasi permintaan pembelian, meminta penawaran, membuat PO, mencatat penerimaan barang | Harian |
| Pemeriksa kas | Mencocokkan rekap kas Finance A dengan slip setoran dan mutasi rekening; hitung ulang kas fisik secara uji petik | Harian/Mingguan |
| Rekonsiliasi bank (QRIS & transfer) | Cocokkan settlement QRIS dan transfer masuk, tangani selisih/MDR | Harian |
| Insiden selisih | Menindaklanjuti insiden, meminta keterangan kasir, mengusulkan penyelesaian ke Owner | Sesuai kejadian |
| Gaji & tunjangan | Rekap absensi, hitung gaji, siapkan transfer massal tanggal 6 | Bulanan |
| Fee franchise 5% | Hitung, terbitkan tagihan, pantau pembayaran | Bulanan |
| Laporan | Laba rugi per unit, buku kas, rekap pengeluaran per jenis, analisa penjualan | Mingguan/bulanan |
| Tutup periode | Rekonsiliasi akhir periode, ajukan tutup buku ke Owner | Bulanan |

### 2.4 Pembagian kerja harian (contoh jadwal)

| Waktu | Finance A | Finance B |
| --- | --- | --- |
| 08.00 | Terima kas shift malam, hitung, catat | Periksa pengajuan belanja dapur hari ini |
| 09.00 | Input/rapikan pemasukan kemarin, cek QRIS masuk | Verifikasi nota belanja, siapkan pembayaran |
| 10.00 | Setor tunai ke bank, unggah slip | Transfer pembayaran vendor, unggah bukti |
| 13.00 | Catat pemakaian kas kecil | Cocokkan setoran A dengan mutasi rekening |
| 15.00 | Terima kas shift siang | Verifikasi pengeluaran operasional (listrik, gas, dsb) |
| 20.00 | Tutup kas harian bersama kasir | Cek selisih & buat insiden bila perlu |
| 21.00 | Rekap kas harian ke B | Rekap harian: kas masuk, kas keluar, saldo bank |

### 2.5 Kontrol silang & rotasi

1. **Setiap Jumat** saling bertukar peran untuk satu hari (A memeriksa pengeluaran, B menghitung kas).
   Tujuannya agar tidak ada satu orang yang menjadi satu-satunya sumber kebenaran.
2. **Setiap awal bulan** Owner melakukan uji petik: hitung kas kecil, minta 3 slip setoran acak,
   dan cocokkan dengan mutasi rekening.
3. **Cuti/izin**: pengganti memakai akun sendiri (jangan meminjam akun), lalu dilakukan pemeriksaan
   ulang oleh finance yang kembali.
4. **Semua penyimpangan** (kas kurang, setoran terlambat, nota hilang) dicatat sebagai insiden di
   aplikasi, bukan diselesaikan lewat chat.

### 2.6 Hak akses di aplikasi

Saat ini kedua finance memakai peran **Admin Keuangan** (`admin_keuangan`): input pemasukan,
pengeluaran, rekonsiliasi, insiden, laporan, master data. Owner memegang koreksi, pembatalan,
write-off, tutup periode, dan pengaturan.

Yang perlu dibiasakan:

- **Satu orang satu akun.** Jangan berbagi login — maker-checker jadi tidak bermakna bila akun dipakai bersama.
- Karena maker-checker menolak verifikasi transaksi sendiri, transaksi yang diinput Finance A otomatis
  harus diverifikasi Finance B, dan sebaliknya. Ini justru kontrol yang diinginkan.
- Bila nanti perlu pembatasan lebih ketat (A tidak boleh menyetujui pengeluaran, B tidak boleh
  memegang kas kecil), tambahkan izin granular `kas.kelola`, `pengeluaran.bayar`, `penggajian.kelola`
  dan pemisahan menu per finansial.

---

## 3. Pertanyaan 2 — Sistem penyetoran uang cash ke Rekening Forsera House

### 3.1 Aturan pokok yang disarankan

| Aturan | Nilai yang disarankan | Alasan |
| --- | --- | --- |
| Cut-off setoran | H+1, sebelum pukul 12.00 | Uang tunai tidak menginap lebih dari 1 malam |
| Kas di tangan (float) | Maks Rp 500.000 per unit | Cukup untuk kembalian, risiko hilang kecil |
| Penghitungan | Selalu 2 orang (kasir + finance), dihitung dua kali | Mengurangi selisih hitung |
| Amplop/slip | Bernomor urut, disegel, ditandatangani | Bukti serah terima |
| Batas selisih setor | Rp 0 terhadap bank | Uang fisik dan nominal setor harus sama persis |
| Toleransi kas harian | Rp 1.000 (sesuai pengaturan `toleransi_selisih`) | Selisih kecil karena pembulatan |
| Keterlambatan setor | Insiden otomatis > 3 hari | Uang mengendap = risiko |
| Rekening tujuan | Satu rekening penampung **Forsera House**, rekening unit tidak dipakai | Memudahkan konsolidasi & audit |

### 3.2 Alur empat tahap

**Tahap 1 — Tutup kas kasir (per shift).**

Kasir menghitung kas fisik, mengisi form tutup kas:

- total penjualan tunai menurut sistem (aplikasi/POS),
- total penjualan QRIS (tidak masuk kas),
- pengeluaran yang diambil dari kas (mis. belanja mendadak, uang makan),
- kas fisik yang diserahkan,
- selisih dan penjelasan.

Kasir dan Finance A menandatangani. Ini yang di aplikasi disebut **berita acara serah terima kas**.

**Tahap 2 — Serah terima kasir ke finance.**

Finance A menerima kas, menghitung ulang, mencatat di rekap kas harian per unit. Kas disimpan di
brankas/kotak terkunci, bukan di meja.

**Tahap 3 — Setoran ke bank.**

Perhitungan yang harus selalu sama:

```
Nominal setor = pemasukan tunai hari itu
              − pengeluaran yang dibayar dari kas (dari_kas_harian)
              − kas di tangan yang disisakan (float)
```

Contoh: pemasukan tunai Rp 2.400.000, belanja dapur dari kas Rp 350.000, float Rp 200.000
→ setor **Rp 1.850.000**.

Finance A menyetor di teller/CRM, mendapat slip, memfoto slip, dan mencatat **tanggal setor** serta
**nominal setor** di aplikasi. Slip asli disimpan di map harian.

**Tahap 4 — Pencocokan mutasi rekening.**

Finance B membuka mutasi rekening Forsera House:

- cocokkan tanggal kredit, nominal, dan berita transfer,
- bila ada biaya transfer, catat sebagai biaya bank (bukan selisih),
- bila bank mengkredit beda hari, jangan langsung buat insiden — tagih konfirmasi bank,
- setelah cocok, pemasukan tunai ditandai **Selesai**.

### 3.3 Cara memakainya di aplikasi sekarang

Yang sudah tersedia:

- **Pemasukan** dengan metode Tunai / QRIS / Transfer, status `Belum Dicek`.
- **Rekonsiliasi** memilih sekumpulan pemasukan dalam rentang tanggal, mengisi **total diterima**,
  **biaya admin/MDR**, dan **potongan kas** (otomatis dari pengeluaran bertanda *dari kas harian*).
- Rumus selisih: `total diterima − (total dicatat − biaya admin − potongan kas)`.
- Selisih di luar toleransi Rp 1.000 otomatis membuat **insiden**.
- Tunai tidak boleh ditandai "menunggu settlement" (hanya QRIS/transfer).
- Laporan **buku kas** per unit dan **rekonsiliasi ringkasan**.

Karena itu, untuk tunai, lakukan seperti ini sampai fitur setoran dibuat:

1. Input semua pemasukan tunai shift (boleh per shift sebagai satu baris, catatan berisi nomor slip).
2. Input pengeluaran yang diambil dari kas dengan centang **dari kas harian**.
3. Rekonsiliasi tunai per unit per hari dengan **total diterima = nominal yang benar-benar disetor**.
   `potongan kas` akan muncul otomatis sehingga rumus cocok tanpa hitung manual.
4. Unggah slip setoran sebagai lampiran rekonsiliasi.
5. Bila setoran dilakukan H+1, tetap rekonsiliasi pada tanggal transaksi dengan tanggal diterima
   hari setor — jangan menunda input pemasukan.

**Keterbatasan yang perlu diketahui:**

- Aplikasi belum punya entitas **setoran** tersendiri, sehingga satu setoran bank untuk beberapa hari
  atau beberapa unit tetap harus dipecah per rekonsiliasi.
- Belum ada master **rekening bank**, jadi nomor rekening tujuan hanya bisa ditulis di catatan.
- Belum ada penanda "uang dalam perjalanan" (cash in transit).

### 3.4 Yang perlu dibangun (usulan)

| Komponen | Isi minimal |
| --- | --- |
| `rekening` | nama, nomor, bank, pemilik, unit, saldo awal, aktif |
| `setoran` | kode `SET-YYYYMM-####`, unit, rekening, tanggal setor, nominal fisik, nominal setor, biaya transfer, selisih, bukti slip, status (DRAFT/DIKIRIM/COCOK/BATAL), diverifikasi_oleh |
| `setoran_pemasukan` | relasi setoran ↔ pemasukan (banyak pemasukan, satu setoran) |
| Rekonsiliasi bank | impor mutasi (CSV) atau input manual, pencocokan otomatis nominal+tanggal, tandai cocok |
| Laporan | rekap setoran per unit/hari, umur setoran (berapa hari uang mengendap), selisih setoran |

Aturan validasi yang disarankan:

- `nominal_setor` wajib sama dengan `total_diterima` pada rekonsiliasi tunai terkait.
- Setoran tidak boleh melebihi total pemasukan tunai yang belum disetor.
- Setoran hanya bisa dibatalkan Owner dengan alasan, dan pemasukan kembali ke status belum dicek.
- Setoran yang belum cocok dengan mutasi > 3 hari masuk panel **Perlu Perhatian** (sudah ada polanya
  di aplikasi untuk `batas_hari_settlement`).

---

## 4. Pertanyaan 3 — Sistem petty cash (kas kecil)

### 4.1 Model yang disarankan: sistem imprest

Prinsipnya: **saldo kas kecil selalu kembali ke plafon**. Pemakaian dicatat, lalu diisi ulang sebesar
pemakaian, bukan sesuka hati.

| Parameter | Saran | Catatan |
| --- | --- | --- |
| Plafon | Rp 2.000.000 per unit | Sekitar 3–5 hari belanja kecil; sesuaikan volume |
| Pemegang | Finance A | Satu orang, tercatat, tidak dirangkap kasir |
| Batas per transaksi | Rp 300.000 | Di atas itu wajib lewat pengeluaran/transfer |
| Pengisian ulang | Saat saldo ≤ 40% plafon, atau tiap Senin | Bukti pemakaian lengkap dulu |
| Bukti | Nota/kwitansi asli + foto | Tanpa bukti = tidak diisi ulang |
| Opname | Awal bulan oleh Finance B, uji petik Owner | Fisik vs catatan, selisih = insiden |
| Larangan | Gaji, sewa, pembelian > batas, pembayaran vendor berjangka, uang muka proyek | Gunakan jalur pengeluaran biasa |

Kas kecil **bukan** tempat menampung uang makan 40rb/hari bila karyawan banyak. Contoh: 25 karyawan ×
Rp 40.000 × 26 hari = **Rp 26 juta/bulan** — jauh di atas plafon. Perlakukan sebagai:
rekap mingguan per karyawan dengan bukti tanda terima, dibayar transfer/tunai dari pengeluaran
*Gaji dan Upah*, bukan dari kas kecil (lihat bagian 8.3).

### 4.2 Perlakuan akuntansi (penting agar tidak dobel)

- **Pengisian ulang kas kecil bukan beban.** Itu hanya perpindahan uang dari bank ke kas kecil.
- **Beban muncul saat kas kecil dipakai**, yaitu saat nota belanja dicatat sebagai pengeluaran.
- Karena itu di aplikasi: setiap pemakaian kas kecil harus tetap menjadi baris **Pengeluaran**
  (jenis sesuai, metode Tunai, catatan "kas kecil", lampiran nota).
- Saldo kas kecil = plafon + total pengisian − total pemakaian. Ini yang dicocokkan saat opname.

### 4.3 Alur harian

1. Unit/karyawan mengajukan kebutuhan (mis. gas, tisu, sabun, parkir, konsumsi rapat).
2. Finance A menyerahkan uang, menerima nota; bila nota belum ada, pakai bon sementara bertanda tangan.
3. Hari yang sama: catat pengeluaran di aplikasi (lampiran foto nota).
4. Bon sementara wajib diganti nota asli maksimal 3 hari; lewat dari itu ditagih ke penerima.
5. Saat saldo menipis: Finance A membuat rekap pemakaian → Finance B memeriksa → transfer pengisian →
   catat sebagai mutasi kas kecil (bukan pengeluaran baru).

### 4.4 Cara memakainya di aplikasi sekarang

- Buat jenis pengeluaran khusus, mis. **"Kas Kecil"**, atau tetap pakai jenis aslinya (Bahan Baku,
  Listrik/Air/Internet, Lain-lain) dengan catatan `kas kecil` — cara kedua lebih baik untuk laporan
  karena beban tetap terkelompok benar.
- Gunakan centang **dari kas harian** hanya bila uangnya diambil dari kas penjualan unit, dan **jangan**
  dicentang bila diambil dari kas kecil, agar potongan setoran tidak salah hitung.
- Kekurangan: belum ada saldo kas kecil berjalan otomatis, belum ada opname, belum ada pengisian ulang.

### 4.5 Yang perlu dibangun

| Komponen | Isi |
| --- | --- |
| `kas_kecil` | unit, pemegang (user), plafon, saldo, aktif |
| `kas_kecil_mutasi` | tanggal, tipe (PENGISIAN/PEMAKAIAN/PENGEMBALIAN/KOREKSI), nominal, pengeluaran_id, bukti, saldo_setelah |
| `kas_kecil_opname` | tanggal, saldo sistem, saldo fisik, selisih, catatan, disetujui_oleh |
| Validasi | pemakaian tidak boleh melebihi saldo; opname selisih > toleransi membuat insiden |
| Laporan | buku kas kecil per unit, rekap pemakaian per jenis, selisih opname |

---

## 5. Pertanyaan 4 — Form pengadaan barang dan jasa

### 5.1 Dua jalur, jangan dicampur

| Jalur | Dipakai untuk | Dokumen | Waktu |
| --- | --- | --- | --- |
| **A. Bon belanja harian** | Kebutuhan mendesak/harian: bahan baku pasar, gas, es, bumbu | 1 form ringkas (permintaan + realisasi) + nota | Hari yang sama |
| **B. Permintaan pembelian (PR) → PO** | Nilai besar, barang stok, jasa/vendor, peralatan | PR → persetujuan → PO → penerimaan barang → tagihan | 1–7 hari |

Dapur yang butuh belanja hari ini cukup memakai **jalur A**; jangan memaksa PR berlapis untuk
belanja Rp 200.000 karena akan dilewati orang.

### 5.2 Form A — Bon belanja harian

Diisi oleh kepala dapur/unit, diverifikasi finance, disetujui sesuai batas.

| Bagian | Field |
| --- | --- |
| Header | nomor bon (`BB-YYYYMMDD-###`), tanggal, unit, pemohon, bagian (dapur/bar/barber) |
| Kebutuhan | daftar item: nama, jumlah, satuan, perkiraan harga, subtotal, catatan |
| Total | total perkiraan, uang muka (jika ada) |
| Realisasi | harga aktual per item, total aktual, selisih, sisa uang dikembalikan |
| Lampiran | foto nota/kwitansi, foto barang (opsional) |
| Tanda tangan | pemohon, pemberi uang (Finance A/B), penerima barang |

Aturan: total aktual tidak boleh melebihi perkiraan > 10% tanpa persetujuan ulang; sisa uang
dikembalikan hari itu juga.

### 5.3 Form B — Permintaan pembelian (PR) / PO

| Bagian | Field |
| --- | --- |
| Header | nomor PR (`PR-YYYYMM-###`), tanggal, unit, pemohon, departemen, tanggal dibutuhkan, prioritas |
| Justifikasi | alasan kebutuhan, dampak bila tidak dibeli, referensi anggaran |
| Detail | item/jasa, spesifikasi, jumlah, satuan, harga perkiraan, subtotal |
| Vendor | nama pemasok, kontak, NPWP (opsional), penawaran terlampir (minimal 2 di atas batas) |
| Persetujuan | Finance (verifikasi anggaran & harga), Owner (di atas batas), tanggal & catatan |
| PO | nomor PO, vendor terpilih, termin pembayaran, tanggal kirim |
| Penerimaan | tanggal terima, jumlah diterima, kondisi (baik/rusak/kurang), nama penerima, foto |
| Tagihan | nomor invoice vendor, jatuh tempo, tanggal bayar, bukti transfer |

Status yang disarankan: `DRAFT → DIAJUKAN → DIVERIFIKASI → DISETUJUI/DITOLAK → DIPO → DITERIMA →
DITAGIH → DIBAYAR → SELESAI`, plus `DIBATALKAN`.

### 5.4 Matriks persetujuan (usulan awal, sesuaikan)

| Nilai pengadaan | Persetujuan | Penawaran | Catatan |
| --- | --- | --- | --- |
| ≤ Rp 300.000 | Finance (1 orang) | 1 nota | Bon belanja harian |
| Rp 300.001 – Rp 1.500.000 | Finance B + catatan Finance A | 1–2 | PR ringkas |
| Rp 1.500.001 – Rp 5.000.000 | Owner | minimal 2 | PR + PO |
| > Rp 5.000.000 / aset tetap | Owner + rencana anggaran | minimal 3 | PR + PO + kontrak bila jasa |

Angka ini selaras dengan pengaturan aplikasi `batas_persetujuan_pengeluaran` (0 = nonaktif).
Disarankan diisi **1.500.000** agar pengeluaran besar otomatis menunggu persetujuan Owner.

Catatan penting: saat ini ambang tersebut **hanya berlaku untuk pengeluaran yang diinput peran
`staf_unit`**. Pengeluaran yang diinput Admin Keuangan atau Owner langsung berstatus disetujui,
sehingga matriks di atas belum berlaku penuh untuk dua finance. Agar ambang juga mengikat finance,
ubah `PengeluaranService::tentukanStatus()` menjadi memakai `! $user->isOwner()` (bukan `isStaf()`)
— perubahan kecil, tetapi wajib disertai test agar pengeluaran finance di atas batas benar-benar
menunggu persetujuan Owner dan tidak bisa disetujui sendiri.

### 5.5 Yang sudah ada di aplikasi

✅ Master **jenis pengeluaran** (Bahan Baku, Gaji dan Upah, Listrik/Air/Internet, Sewa, Peralatan,
Pemasaran, Royalti/Fee Franchise, Perawatan, Transport, Lain-lain).
✅ Master **item + satuan** dengan `harga_terakhir`.
✅ **Pengeluaran** dengan **detail item** (jumlah, satuan, harga satuan, subtotal dihitung server),
   lampiran, alur persetujuan, peringatan **lonjakan harga** > 20% dari rata-rata 5 pembelian terakhir.
✅ Laporan **pengeluaran per jenis** dan **per item** (harga rata-rata, min, maks).

### 5.6 Yang belum ada

| Kebutuhan | Dampak bila tidak ada | Usulan |
| --- | --- | --- |
| Master **pemasok/vendor** | Nama vendor hanya di catatan, sulit analisa | Tabel `pemasok` + relasi ke pengeluaran |
| **Permintaan pembelian/PO** | Tidak ada jejak siapa minta, kapan disetujui | Tabel `permintaan_pembelian` + detail + status |
| **Penerimaan barang** | Tidak bisa cocokkan barang datang vs dibayar | Tabel `penerimaan_barang` |
| **Anggaran per unit/per jenis** | Tidak ada peringatan over-budget | Tabel `anggaran` + validasi saat input |
| Tanda tangan/foto penerima | Sengketa "barang tidak diterima" | Field `penerima` + lampiran |

Urutan prioritas: pemasok → PR → penerimaan barang → anggaran. Bon belanja harian bisa dimulai
sebagai penyederhanaan PR (satu tabel dengan flag `jenis: HARIAN|PR`).

---

## 6. Pertanyaan 5 — Analisa penjualan (best seller & produk tidak laku)

### 6.1 Kendala data hari ini

Aplikasi mencatat pemasukan **per kategori** (Dine-in, Take away, Online, Kopi, Non-kopi, Jasa Cukur,
Pomade, Parfum) dan **per metode bayar** — bukan per menu. Dengan data ini yang bisa dijawab hanya:

- omzet per unit per hari/bulan,
- komposisi tunai vs QRIS,
- kanal penjualan (dine-in/take away/online),
- kategori mana yang menyumbang omzet terbesar.

**Best seller per produk tidak bisa dihitung** tanpa data level menu. Ini bukan masalah laporan,
tetapi masalah data sumber.

### 6.2 Data minimum yang harus dikumpulkan

| Field | Contoh | Sumber |
| --- | --- | --- |
| Tanggal & jam | 2026-10-20 19:00 | POS / rekap kasir |
| Unit | Forsera Resto | POS |
| Menu/item | Nasi Goreng Forsera | POS |
| Qty | 12 | POS |
| Harga jual | 35.000 | Master menu |
| Diskon/promo | 5.000 | POS |
| Kanal | Dine-in | POS |
| HPP per porsi | 14.500 | Resep/BOM dapur |

Dua cara mendapatkannya:

1. **Impor ekspor POS (disarankan).** Minta export harian/bulanan berisi menu, qty, harga, diskon.
   Buat form unggah CSV/Excel → validasi → simpan. Tidak membebani kasir.
2. **Rekap menu harian manual.** Kasir mengisi qty per menu saat tutup shift. Lebih rawan salah dan
   menambah pekerjaan; pakai hanya bila POS tidak bisa ekspor.

Data menu ini juga menjadi **kontrol tambahan**: total penjualan menu harus sama dengan pemasukan
yang dicatat. Bila beda, itu sinyal ada transaksi tidak tercatat.

### 6.3 Metrik yang dipakai

| Pertanyaan | Metrik | Rumus |
| --- | --- | --- |
| Produk paling laku | Qty terjual, revenue | `SUM(qty)`, `SUM(qty × harga − diskon)` |
| Produk paling menguntungkan | Margin kontribusi | `revenue − (qty × HPP)` |
| Produk tidak laku | Slow mover | qty = 0 atau di bawah 5% rata-rata menu selama 7/30 hari |
| Produk untuk promo | Margin tinggi + qty rendah | lihat matriks di bawah |
| Efek promo | Uplift | qty/omzet promo vs periode sebelumnya |
| Pola beli bersama | Attachment | pasangan menu yang sering muncul di satu struk |

### 6.4 Matriks menu (menu engineering)

Bagi menu berdasarkan **popularitas** (qty share terhadap rata-rata) dan **profitabilitas**
(margin kontribusi per porsi terhadap rata-rata):

|  | Margin tinggi | Margin rendah |
| --- | --- | --- |
| **Laku** | ⭐ **Star** — pertahankan, tampilkan paling depan, jaga konsistensi | 🐴 **Plowhorse** — naikkan harga bertahap, kurangi porsi mahal, bundling |
| **Kurang laku** | 🧩 **Puzzle** — dorong lewat upsell, saran pelayan, promo terbatas | 🐕 **Dog** — hapus atau ganti resep/harga |

Aturan aksi yang bisa langsung dipakai:

- **Star**: jadikan paket/combo, jangan ubah resep.
- **Plowhorse**: naikkan harga 5–10% atau kurangi biaya bahan; jangan dihapus karena masih menarik tamu.
- **Puzzle**: latih pelayan menawarkan, letakkan di posisi menu atas, promo "coba gratis" terbatas.
- **Dog**: evaluasi 30 hari, bila tetap rendah keluarkan dari menu (hindari beli bahan yang akhirnya rusak).

Ambang yang disarankan: menu dengan penjualan < 5 porsi/hari selama 14 hari berturut-turut masuk
daftar evaluasi; menu tanpa penjualan 30 hari masuk daftar hapus.

### 6.5 Laporan dan jadwal review

| Laporan | Isi | Jadwal |
| --- | --- | --- |
| Top & bottom menu | 10 terlaris & 10 terendah per unit, qty, omzet, margin | Mingguan |
| Matriks menu | Sebaran star/plowhorse/puzzle/dog | Bulanan |
| Menu tidur | Tidak terjual 7/14/30 hari | Mingguan |
| Promo | Menu promo vs non-promo: qty, omzet, margin, kanal | Per kampanye |
| Kanal & jam | Omzet per kanal dan per jam (dine-in ramai jam berapa) | Bulanan |
| Rekonsiliasi penjualan | Total menu vs pemasukan tercatat | Harian |

Tindak lanjut wajib: setiap review bulanan menghasilkan **3 keputusan** — apa yang dipromosikan,
apa yang harganya diubah, apa yang dihapus. Tanpa keputusan, laporan hanya menjadi arsip.

### 6.6 Yang perlu dibangun

| Komponen | Isi |
| --- | --- |
| `menu` | nama, unit, kategori, harga jual, HPP, aktif, tanggal mulai/selesai |
| `penjualan_menu` | tanggal, unit, menu_id, qty, harga, diskon, kanal, sumber (POS/MANUAL) |
| Impor | unggah CSV/Excel, pemetaan kolom, validasi, pratinjau, batalkan impor |
| Widget dashboard | Top 5 menu, menu tidur, total qty vs pemasukan |
| Laporan | matriks menu, margin per menu, tren 12 minggu, attachment/upsell |

Alternatif tanpa membangun banyak: mulai dari **impor bulanan** ke spreadsheet lalu tempel ringkasan
ke laporan; tetapi keputusan promo harian akan tetap lambat.

---

## 7. Pertanyaan 6 — Fee manajemen franchise 5%

### 7.1 Dasar pengenaan harus ditulis dulu

"5% dari nilai transaksi" bisa berarti tiga hal berbeda. Rekomendasi: **5% × penjualan neto**
(omzet setelah diskon, sebelum pajak, tidak termasuk biaya layanan), dengan pengecualian:

- tidak termasuk MDR QRIS/biaya payment gateway (itu biaya bank, bukan omzet),
- tidak termasuk kategori **Pendapatan Lain-lain** (bunga, penjualan aset, titipan),
- tidak termasuk uang muka/pesanan yang belum menjadi penjualan,
- retur/refund mengurangi omzet pada periode terjadinya.

Contoh: omzet Kopi Arobi Oktober Rp 150.000.000 → fee 5% = **Rp 7.500.000**.

Bila di kemudian hari franchisee melaporkan omzetnya sendiri (beda sistem), tambahkan mekanisme
**laporan omzet franchisee** yang diverifikasi; jangan menghitung dari data internal yang tidak lengkap.

### 7.2 Alur

| Langkah | Waktu | Pelaksana | Dokumen |
| --- | --- | --- | --- |
| Hitung omzet dasar | Setelah periode tutup (tgl 5) | Sistem | Rekap omzet per unit franchise |
| Terbitkan tagihan | tgl 5–6 | Finance B | Invoice `INV-FR-YYYYMM-###`, jatuh tempo tgl 10 |
| Persetujuan | tgl 6 | Owner | Approve nominal & dasar perhitungan |
| Pembayaran | ≤ tgl 10 | Franchisee | Bukti transfer |
| Pencocokan | tgl 10–12 | Finance B | Tandai lunas + unggah bukti |
| Tindak lanjut | > tgl 10 | Finance B | Pengingat, lalu insiden/teguran |

Ketentuan yang perlu ada di perjanjian: jatuh tempo, denda keterlambatan (mis. 1%/bulan), hak audit,
dan dasar pengenaan (neto/bruto) agar tidak diperdebatkan.

### 7.3 Perlakuan akuntansi

- Bagi **Forsera Group sebagai pemberi franchise**, fee adalah **pendapatan manajemen**, bukan
  pengurang omzet unit.
- Bagi **unit yang membayar** (bila dikelola perusahaan sendiri), fee adalah beban internal —
  harus dieliminasi saat konsolidasi agar laba grup tidak terdistorsi.
- Karena itu, pisahkan unit **milik sendiri** dan **franchisee**: hanya unit dengan tipe
  `KOPI_FRANCHISE` (dan unit franchisee lain) yang memicu tagihan.
- Bila fee dipotong langsung dari setoran unit (net settlement), catat sebagai pemasukan
  "Pendapatan Manajemen" + pengeluaran unit sebesar fee, jangan hanya mengurangi angka setoran.
- Pajak: jasa manajemen umumnya dikenai PPh 23 (2%) bila penyedia berbadan hukum. Konfirmasikan
  ke konsultan pajak sebelum menerbitkan invoice pertama.

### 7.4 Yang perlu dibangun

| Komponen | Isi |
| --- | --- |
| Pengaturan | `persen_fee_franchise` (default 5), `dasar_fee` (NETO/BRUTO), `jatuh_tempo_fee_hari` (5), `denda_fee_persen` |
| `tagihan_franchise` | kode, unit, periode, dasar omzet, persen, nominal fee, pajak, total, jatuh tempo, status (DRAFT/TERBIT/DIBAYAR/TERLAMBAT/BATAL), bukti bayar |
| Generate | Otomatis saat tutup periode atau tombol "Hitung fee periode ini"; simpan rincian omzet yang dipakai |
| Laporan | rekap fee per unit/periode, umur piutang fee, tren omzet franchise |
| Cetak | invoice PDF memakai identitas aplikasi (brand/logo yang sudah ada) |

Validasi penting: fee tidak boleh dihitung dari pemasukan yang masih berstatus selisih/insiden
belum selesai; tunggu rekonsiliasi selesai agar dasar omzet bersih.

---

## 8. Pertanyaan 7 — Gajian tanggal 6

### 8.1 Kalender yang disarankan

Periode aplikasi 5 s/d 4 sudah dirancang agar gajian tanggal 6 jatuh **setelah** periode tutup.

| Tanggal | Aktivitas | Penanggung jawab |
| --- | --- | --- |
| 3 | Tutup data absensi, lembur, izin, kasbon; kunci perubahan absensi | HR/unit |
| 4 | Hitung gaji, potongan, BPJS, PPh 21; kirim rekap ke Owner | Finance B |
| 5 | Owner menyetujui; tutup periode akuntansi; siapkan file transfer massal | Owner + Finance B |
| 6 | Transfer gaji; bagikan slip; arsipkan bukti | Finance B |
| 6–7 | Catat pengeluaran gaji per unit + unggah bukti transfer | Finance B |
| 10 | Batas laporan koreksi gaji (kurang/lebih bayar diproses periode berikutnya) | Finance B |

Aturan: gaji **tidak dibayar dari kas kecil** dan tidak dipotong dari setoran kas harian. Bila
terpaksa tunai, siapkan dana khusus dan berita acara per karyawan.

### 8.2 Komponen data yang dibutuhkan

| Data | Contoh |
| --- | --- |
| Master karyawan | nama, NIK, jabatan, unit, tanggal masuk, status, rekening |
| Gaji pokok | 3.000.000 |
| Tunjangan | makan, transport, jabatan |
| Potongan | absensi, kasbon, BPJS karyawan, PPh 21 |
| Lembur | jam × tarif |
| Uang makan | 40.000 × hari hadir |

Perhitungan: **gaji bersih = pokok + tunjangan + lembur − potongan − BPJS − PPh 21**.

### 8.3 Uang makan Rp 40.000/hari

Tiga pilihan, pilih satu agar tidak bercampur:

1. **Masuk payroll (disarankan bila karyawan > 15).** Rekap hari hadir per periode, dibayar
   bersama gaji tanggal 6. Paling sedikit transaksi, paling mudah diaudit.
2. **Rekap mingguan, transfer/tunai.** Setiap Sabtu rekap 6 hari hadir × 40.000, satu pengeluaran
   per unit dengan lampiran daftar nama + tanda terima.
3. **Kas kecil harian.** Hanya cocok bila karyawan sedikit dan kas kecil cukup; tidak disarankan
   karena cepat menguras plafon dan rawan tanpa bukti.

Apa pun pilihannya, **satu hari hadir = satu baris absensi**, bukan nominal yang diketik bebas.
Bila uang makan diberikan tunai, wajib ada tanda terima (bisa satu lembar untuk seminggu).

### 8.4 Perlakuan akuntansi & periode

- Gaji Oktober yang dibayar 6 November secara kas masuk **periode November** (5 Nov–4 Des). Bila
  Owner ingin beban gaji menempel pada periode Oktober, buat **akrual utang gaji** saat tutup buku
  tanggal 5, lalu pembayaran 6 November mengurangi utang — bukan menambah beban baru.
- Pilih satu kebijakan dan tulis di dokumen kebijakan akuntansi. Jangan campur: sebagian akrual,
  sebagian kas, karena laba per periode jadi tidak bisa dibandingkan.
- Rekomendasi praktis: **kas basis** dulu (lebih sederhana), dengan catatan bahwa laba periode
  berjalan memuat gaji bulan lalu. Bila laporan mulai dipakai untuk mengambil keputusan harga,
  baru beralih ke akrual.

### 8.5 Yang perlu dibangun

| Komponen | Isi |
| --- | --- |
| `karyawan` | master karyawan (bukan user aplikasi), unit, jabatan, gaji pokok, rekening, status |
| `absensi` | tanggal, karyawan, status hadir/izin/sakit/alpha, jam lembur |
| `penggajian` | periode, unit, jumlah karyawan, bruto, total potongan, netto, status (DRAFT/DISETUJUI/DIBAYAR), disetujui_oleh |
| `penggajian_detail` | karyawan, hari hadir, gaji pokok, tunjangan, lembur, potongan, netto |
| Cetak | slip gaji PDF per karyawan, rekap transfer bank (nomor rekening + nominal) |
| Integrasi | saat status DIBAYAR, otomatis membuat satu pengeluaran per unit (jenis Gaji dan Upah) agar laba rugi tetap satu sumber |

Menjaga konsistensi: jangan mencatat gaji dua kali (sekali di menu penggajian, sekali input manual
di pengeluaran). Pilih penggajian sebagai sumber, pengeluaran sebagai jejak pembayaran.

---

## 9. Ringkasan kesenjangan aplikasi

| Kebutuhan | Status | Prioritas | Catatan |
| --- | --- | --- | --- |
| Pemasukan tunai/QRIS/transfer + lampiran | ✅ | — | Sudah dipakai |
| Pengeluaran + detail item + persetujuan + peringatan harga | ✅ | — | Sudah dipakai |
| Rekonsiliasi tunai dengan potongan kas harian | ✅ | — | Cocok untuk setoran harian |
| Insiden selisih + penyelesaian | ✅ | — | Sudah otomatis |
| Periode 5–4 & gajian tanggal 6 | ✅ | — | Default aplikasi |
| Identitas aplikasi (logo/nama/brand/title) | ✅ | — | Untuk invoice & laporan |
| Master rekening bank | ❌ | Tinggi | Dibutuhkan setoran & fee |
| Setoran kas (kasir → finance → bank) | ❌ | Tinggi | Menutup celah "uang di jalan" |
| Kas kecil (saldo, mutasi, opname) | ❌ | Tinggi | Kontrol pengeluaran harian |
| Pemasok/vendor + PR/PO + penerimaan barang | ❌ | Sedang | Pengadaan barang & jasa |
| Master menu + penjualan per menu | ❌ | Tinggi | Syarat analisa best seller |
| Fee franchise 5% + invoice | ❌ | Sedang | Bisa manual dulu, sebaiknya otomatis |
| Master karyawan + penggajian | ❌ | Sedang | Gaji tanggal 6 |
| Anggaran per unit | ❌ | Rendah | Setelah data pengeluaran rapi |
| Ambang persetujuan berlaku untuk input finance | ⚠️ | Tinggi | Saat ini hanya `staf_unit`; perlu ubah `tentukanStatus()` |
| Pemisahan izin finance A/B | ⚠️ | Rendah | Cukup prosedural + maker-checker |

Urutan implementasi yang disarankan (dampak terbesar lebih dulu):

1. **Rekening + setoran kas + opname kas kecil** — menutup risiko uang tunai, paling mendesak.
2. **Master menu + impor penjualan** — membuka analisa best seller dan kontrol omzet.
3. **PR/PO + pemasok** — merapikan pengadaan dan menghindari belanja tanpa bukti.
4. **Karyawan + penggajian + fee franchise** — otomasi rutin bulanan.

---

## 10. Keputusan yang perlu diambil Owner

1. Setoran tunai **H+0 atau H+1**? Berapa float per unit?
2. Plafon kas kecil per unit dan batas nominal per transaksi?
3. Batas nominal yang **wajib persetujuan Owner** (usulan Rp 1.500.000)?
4. Dasar fee franchise: **neto** atau **bruto**, dan apakah fee dipotong dari setoran?
5. Gaji: **kas basis** atau **akrual**? Uang makan 40rb masuk payroll atau rekap mingguan?
6. Siapa pemegang kas kecil: Finance A, Finance B, atau kasir per unit?
7. Apakah dua kasir memakai akun aplikasi sendiri (disarankan) atau tetap diinput finance?

Jawaban atas tujuh hal ini menentukan konfigurasi pengaturan dan bentuk form yang dibangun
berikutnya.
