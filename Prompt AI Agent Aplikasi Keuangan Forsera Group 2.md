FORSERA HOUSE memiliki sejumlah usaha:
1. FORSERA RESTO- Menjual makanan
2. ⁠KOPI AROBI- Menjual Kopi (Franchise)

Aplikasi Forsera Resto dan Kopi Arobi dijadikan 1 (1 kasir 1 tab). Transaksi disatukan.

Hari ini ada finance 2 orang

Transaksi 2 aplikasi ini mengakomodir pembayaran cash dan qris

Qris langsung masuk ke rekening Forsera House dan cash dipegang oleh 2 kasir untuk selanjutnya diserahkan ke finance dan finance setorkan ke rekening Forsera House 

Selain mengurus pemasukan transaksi, finance juga bertanggungjawab mengurus pengeluaran.

Pengeluaran harian:
1. Pembelian bahan baku dapur
2. ⁠Cash Out Karyawan (Setiap Karyawan mendapat uang makan 40rb/ hari)
3. ⁠Listrik
4. ⁠Dan semacamnya


Pertanyaan:
1. Rumuskan tupoksi masing masing finance secara rinci
2. ⁠Bagaimana baiknya sistem penyetoran ke Rekening Forsera House terhadap uang cash dari transaksi
3. ⁠Bagaimana baiknya sistem paty cash
4. ⁠Untuk pengadaan barang dan jasa apa form yang harus dibuat finance? Misalnya dapur membutuhkan pembelian bahan baku untuk hari ini
5. ⁠Finance diharapkan bisa menganalisa penjualan untuk melihat produk mana yang best seller, produk mana yang tidak laku sehingga butuh promo atau upselling
6. ⁠Untuk franchise dikenakan biaya manajemen Fee 5% dari nilai transaksi
7. ⁠Gajian karyawan dilakukan setiap tanggal 6



1. FORSERA RESTO- Menjual makanan
2. ⁠KOPI AROBI- Menjual Kopi (Franchise)
3. ⁠FORSERA BARBERSHOP- Cukur rambut, Menjual Pomade dan Parfum

saya mau buat aplikasi pencatatan keuangan pemasukan dan pengeluaran dari ketiga aplikasi itu
konsep sederhananya, tapi mungkin di tambahkan insidenya.

saya mau ada dashboard,
kemudian saya bisa aplikasi benar-benar bisa memonitoring keuangan

pemasukan

tanggal
sumber -> master : FORSERA RESTO, ⁠KOPI AROBI, DLL
metode pembayaran -> master : QRIS, TUNAI, TRANSFER
nominal
note
lampirans -> json array

ada fitur cros cek ke owner apakah sudah sesuai jumlahnya yang masuk kerekening kalau pake QRIS atau transfer, tunai juga harus dicross cek,
jadi ada status gitu, kemudian gimana kalau kurang atau lebih.

pengeluaran
tanggal
sumber -> master
jenis pengeluaran -> master
jumlah
satuan -> master
nominal
note
lampirans -> json array

detail_pengeluaran
pengeluaran_id
item_id
price
jumlah

item
name

saya mau menggunakan
laravel 11 + react js + primevue

buat prompt untuk ai agent untuk membuat ini