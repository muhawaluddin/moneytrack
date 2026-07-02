# PRD - Aplikasi Pencatatan Keuangan Pribadi
**Nama Produk (sementara):** MoneyTrack / BukuUntung - Personal Finance
**Versi Dokumen:** 1.0
**Tanggal:** 30 Juni 2026
**Disusun untuk:** Muhammad Awaluddin (Wall) - CV Smart Inovasi

---

## 1. Latar Belakang & Tujuan

Banyak orang kesulitan melacak kondisi keuangan pribadi karena uang tersebar di berbagai sumber: rekening bank, e-wallet, kas tunai, dan lainnya. Tanpa pencatatan yang rapi, sulit mengetahui ke mana uang mengalir, saldo riil tiap sumber dana, dan apakah pengeluaran masih sesuai rencana.

**Tujuan produk:**
1. Memberikan satu tempat untuk mencatat seluruh pemasukan dan pengeluaran dari berbagai sumber kas.
2. Menampilkan saldo real-time per sumber kas (bank, e-wallet, tunai, dll).
3. Mengelompokkan transaksi ke kategori yang lengkap dan fleksibel.
4. Menyediakan laporan dan visualisasi yang membantu pengambilan keputusan keuangan.
5. Pengalaman pengguna senyaman aplikasi mobile native, responsif di berbagai ukuran layar.

**Target pengguna:** individu/pribadi yang ingin mengatur keuangan sendiri (bukan multi-user/tim pada versi awal).

---

## 2. Lingkup (Scope)

### Termasuk (In-Scope v1)
- Manajemen multi-sumber kas (akun)
- Pencatatan transaksi pemasukan, pengeluaran, dan transfer antar akun
- Kategori & sub-kategori transaksi yang dapat dikustomisasi
- Dashboard ringkasan keuangan
- Laporan & grafik (bulanan, kategori, tren)
- Anggaran (budget) per kategori per bulan
- Pencarian, filter, dan export data
- Tampilan responsif mobile-first (PWA-ready)
- Autentikasi & keamanan data pribadi

### Tidak Termasuk (Out of Scope v1)
- Multi-user/keluarga (shared account) — direncanakan v2
- Integrasi otomatis ke API bank (open banking) — direncanakan v2/v3
- Investasi & portofolio saham/kripto — direncanakan v2
- Mode multi-mata uang penuh (v1 fokus IDR, struktur data sudah disiapkan untuk multi-currency di masa depan)

---

## 3. Persona Pengguna

**Persona utama: "Andi" - Profesional/Pengusaha Individu**
- Memiliki beberapa rekening bank, e-wallet (OVO/GoPay/Dana), dan kas tunai.
- Ingin tahu total kekayaan likuid saat ini dan ke mana uang terpakai tiap bulan.
- Menggunakan HP sebagai perangkat utama, sesekali laptop untuk laporan detail.
- Tidak suka aplikasi rumit; ingin input transaksi dalam < 10 detik.

---

## 4. Fitur Utama (Functional Requirements)

### 4.1 Manajemen Sumber Kas (Accounts)
- Pengguna dapat menambah, mengedit, menonaktifkan/menghapus sumber kas.
- Tipe sumber kas (dapat dipilih, dengan ikon & warna berbeda):
  - Bank (Rekening Tabungan/Giro) — dengan field nomor rekening, nama bank
  - E-Wallet (OVO, GoPay, DANA, ShopeePay, dll)
  - Kas Tunai (Cash)
  - Kartu Kredit (saldo dapat negatif/limit)
  - Tabungan/Deposito
  - Lainnya (custom)
- Setiap akun memiliki:
  - Nama akun, tipe, ikon/warna, saldo awal, saldo berjalan (auto-calculated)
  - Status aktif/non-aktif (arsip tanpa hapus histori)
  - Catatan tambahan (opsional)
- Halaman detail akun menampilkan:
  - Saldo saat ini
  - Riwayat transaksi khusus akun tersebut
  - Grafik tren saldo (line chart) per periode
- Total saldo gabungan seluruh akun ditampilkan di dashboard utama (Net Worth likuid).

### 4.2 Pencatatan Transaksi
Tiga jenis transaksi:
1. **Pemasukan (Income)** — menambah saldo akun tujuan
2. **Pengeluaran (Expense)** — mengurangi saldo akun sumber
3. **Transfer** — pindah dana antar akun milik sendiri (tidak dihitung sebagai income/expense dalam laporan)

Field input transaksi:
- Jumlah (nominal, dengan format ribuan otomatis)
- Tanggal & waktu (default: sekarang, dapat diubah)
- Sumber kas (akun)
- Kategori & sub-kategori
- Catatan/deskripsi (opsional)
- Foto struk/bukti (opsional, upload gambar)
- Tag (opsional, untuk pengelompokan lintas kategori, misal: #liburan, #proyekX)
- Status: Lunas/Pending (untuk pengeluaran yang masih piutang/utang)
- Transaksi berulang (recurring): harian/mingguan/bulanan/tahunan (misal: gaji, tagihan listrik, cicilan)

Quick-add: tombol input cepat (floating action button) selalu terlihat di mobile agar transaksi bisa dicatat dalam hitungan detik.

### 4.3 Kategori (Lengkap & Fleksibel)

**Kategori Pemasukan (default):**
- Gaji/Upah
- Bonus/THR
- Usaha/Bisnis
- Freelance/Proyek (relevan: konsultasi IT, dakwah content)
- Investasi (dividen, bunga)
- Hadiah/Pemberian
- Pengembalian Dana (refund)
- Penjualan Aset
- Lainnya

**Kategori Pengeluaran (default, dikelompokkan):**
- **Kebutuhan Pokok:** Makanan & Minuman, Belanja Bulanan, Transportasi, Bahan Bakar
- **Tempat Tinggal:** Sewa/Cicilan Rumah, Listrik, Air, Internet & Pulsa, Perawatan Rumah
- **Kesehatan:** Obat & Vitamin, Konsultasi Dokter, Asuransi Kesehatan
- **Pendidikan:** Buku & Kursus, Sekolah Anak, Pelatihan/Sertifikasi
- **Keluarga & Anak:** Kebutuhan Anak, Pendidikan Anak, Kesehatan Anak
- **Ibadah & Sosial:** Zakat, Infaq/Sedekah, Qurban, Wakaf
- **Hiburan & Gaya Hidup:** Nongkrong/Kafe, Hobi, Langganan (Netflix/Spotify dll), Olahraga (termasuk sepeda)
- **Transportasi & Kendaraan:** Servis Kendaraan, Parkir/Tol, Ojek Online
- **Bisnis/Usaha:** Operasional Usaha, Gaji Karyawan, Marketing, Peralatan Kerja
- **Cicilan & Utang:** Cicilan KPR, Cicilan Kendaraan, Kartu Kredit, Utang Pribadi
- **Pajak & Administrasi:** Pajak, Biaya Admin Bank, Biaya Layanan
- **Lain-lain:** Tidak terduga, Lainnya

Pengguna dapat: menambah kategori/sub-kategori baru, mengedit nama & ikon/warna, menonaktifkan kategori yang tidak terpakai, menyusun ulang urutan tampilan.

### 4.4 Dashboard (Beranda)
- Ringkasan total saldo seluruh sumber kas (Net Worth Likuid)
- Grafik donut: proporsi saldo per sumber kas
- Ringkasan bulan berjalan: total pemasukan vs pengeluaran vs selisih (cashflow)
- Grafik batang: tren pemasukan/pengeluaran 6 bulan terakhir
- Top 5 kategori pengeluaran terbesar bulan ini
- Status anggaran (progress bar per kategori: terpakai vs limit)
- Daftar transaksi terbaru (5-10 transaksi)
- Reminder transaksi berulang yang akan jatuh tempo

### 4.5 Anggaran (Budgeting)
- Pengguna dapat menetapkan limit anggaran per kategori per bulan
- Indikator visual: hijau (aman, <70%), kuning (waspada, 70-100%), merah (melebihi budget)
- Notifikasi saat mendekati/melewati limit
- Salin anggaran bulan sebelumnya ke bulan baru (1 klik)

### 4.6 Laporan Keuangan (Reports)
- **Laporan Bulanan:** total income, expense, saldo bersih, perbandingan dengan bulan sebelumnya (%)
- **Laporan per Kategori:** breakdown nominal & persentase, grafik pie/donut
- **Laporan per Sumber Kas:** mutasi keluar-masuk per akun, saldo akhir
- **Laporan Tren:** grafik garis pemasukan vs pengeluaran per bulan (3/6/12 bulan, custom range)
- **Laporan Tahunan:** ringkasan 12 bulan, kategori terbesar tahun berjalan
- **Cashflow Statement:** arus kas masuk-keluar terstruktur
- Filter laporan: rentang tanggal custom, per akun, per kategori, per tag
- Export laporan: PDF (untuk dicetak/dibagikan) dan Excel/CSV (untuk olah data lanjutan)

### 4.7 Pencarian & Filter
- Pencarian transaksi berdasarkan kata kunci (catatan, kategori, nominal)
- Filter kombinasi: rentang tanggal, akun, kategori, jenis transaksi, tag, rentang nominal
- Sort: terbaru, terlama, nominal tertinggi/terendah

### 4.8 Pengaturan & Keamanan
- Login dengan email/password, opsi biometrik (fingerprint/face ID) di mobile
- PIN/kunci aplikasi tambahan untuk privasi
- Backup & restore data (cloud sync)
- Mata uang default: IDR (format Rupiah otomatis)
- Mode tampilan: terang/gelap (dark mode)
- Pengaturan kategori & akun (lihat 4.1 & 4.3)
- Manajemen data: hapus akun, ekspor seluruh data sebelum hapus

---

## 5. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| **Responsif** | Mobile-first, mengikuti pola UX aplikasi mobile umum (bottom navigation, FAB, gesture swipe untuk hapus/edit transaksi). Tetap nyaman digunakan di tablet & desktop (layout adaptif). |
| **Performa** | Waktu input transaksi < 10 detik; loading dashboard < 2 detik untuk data 1 tahun terakhir. |
| **Desain Visual** | Palet warna menarik & konsisten, mendukung dark mode, ikon kategori jelas dan mudah dikenali, micro-interaction (animasi halus) saat tambah transaksi. |
| **Keamanan** | Enkripsi data sensitif, autentikasi aman, kunci PIN/biometrik opsional. |
| **Ketersediaan Offline** | Input transaksi tetap bisa dilakukan saat offline, sinkronisasi otomatis saat online kembali (PWA + local storage). |
| **Skalabilitas** | Struktur data mendukung penambahan multi-user dan multi-currency di versi mendatang tanpa migrasi besar. |
| **Aksesibilitas** | Kontras warna sesuai standar WCAG AA, ukuran tap target minimal 44x44px untuk mobile. |

---

## 6. Desain & UX (Arahan Visual)

**Konsep:** clean, modern, warm-friendly (bukan kaku seperti aplikasi perbankan formal).

- **Skema warna:** warna utama hijau emerald/teal (asosiasi "uang aman, tumbuh") dipadu aksen oranye/kuning hangat untuk CTA dan notifikasi. Merah untuk pengeluaran/peringatan, hijau untuk pemasukan/aman — pola warna ini konsisten di seluruh grafik dan badge.
- **Tipografi:** font sans-serif modern, ukuran nominal uang dibuat lebih besar & tebal agar mudah dipindai mata.
- **Navigasi mobile:** bottom navigation bar 4-5 menu utama (Beranda, Transaksi, Tambah (FAB tengah), Laporan, Akun/Profil).
- **Kartu akun (account card):** desain mirip kartu fisik (gradient sesuai tipe akun) agar mudah dibedakan sekilas.
- **Empty state & onboarding:** ilustrasi ramah saat data masih kosong, dengan ajakan aksi jelas ("Tambah Sumber Kas Pertama Anda").

---

## 7. Alur Pengguna Utama (User Flow)

1. **Onboarding:** Daftar/Login → Tambah sumber kas pertama (nama, tipe, saldo awal) → Pilih kategori default atau kustomisasi → Masuk ke Dashboard.
2. **Mencatat transaksi:** Tap FAB "+" → Pilih jenis (Income/Expense/Transfer) → Isi nominal, akun, kategori, tanggal, catatan → Simpan → Saldo akun & dashboard otomatis update.
3. **Melihat laporan:** Tap menu Laporan → Pilih periode & filter → Lihat grafik & ringkasan → Export jika perlu.
4. **Cek detail sumber kas:** Tap kartu akun di Beranda → Lihat saldo, riwayat mutasi, grafik tren akun tersebut.
5. **Mengatur anggaran:** Menu Anggaran → Pilih kategori → Set limit bulanan → Pantau progress di Dashboard.

---

## 8. Struktur Data (Ringkasan Model)

- **User**: id, nama, email, password_hash, preferensi (tema, mata uang)
- **Account (Sumber Kas)**: id, user_id, nama, tipe, saldo_awal, saldo_berjalan, warna/ikon, status, no_rekening (opsional)
- **Category**: id, user_id, nama, tipe (income/expense), parent_id (untuk sub-kategori), ikon, warna, status
- **Transaction**: id, user_id, account_id, account_tujuan_id (khusus transfer), category_id, jenis (income/expense/transfer), nominal, tanggal, catatan, foto_bukti, tag, status (lunas/pending), is_recurring, recurring_rule
- **Budget**: id, user_id, category_id, bulan, tahun, limit_nominal
- **Tag**: id, user_id, nama

---

## 9. Rencana Teknis (Saran)

Mengingat preferensi teknologi yang biasa digunakan (Laravel), berikut saran stack:

- **Backend:** Laravel 13 (REST API)
- **Frontend:** Laravel + Livewire/Blade responsif, atau alternatif SPA dengan Vue/Inertia.js untuk pengalaman mobile-app-like yang lebih mulus
- **Database:** MySQL/PostgreSQL
- **Charting:** Chart.js atau ApexCharts untuk grafik laporan
- **PWA:** Service worker untuk dukungan offline & "install to home screen" di mobile
- **Export:** Laravel Excel (export CSV/Excel), DomPDF/Snappy (export PDF)
- **Autentikasi:** Laravel Sanctum/Breeze + opsi biometrik via WebAuthn (mobile browser yang mendukung)

---

## 10. Metrik Keberhasilan (Success Metrics)

- Waktu rata-rata input 1 transaksi < 10 detik
- Pengguna aktif mencatat transaksi minimal 5x/minggu (indikasi kebiasaan terbentuk)
- 90% pengguna dapat menemukan saldo total dalam < 5 detik setelah membuka aplikasi
- Tingkat penggunaan fitur laporan minimal 1x/bulan per pengguna

---

## 11. Roadmap Singkat

| Fase | Fitur |
|---|---|
| **v1 (MVP)** | Akun multi-sumber kas, transaksi income/expense/transfer, kategori lengkap, dashboard, laporan dasar, anggaran, responsif mobile |
| **v1.1** | Export PDF/Excel, transaksi berulang, dark mode, PWA offline |
| **v2** | Multi-user/keluarga (shared wallet), integrasi notifikasi WhatsApp/Telegram untuk reminder, scan struk otomatis (OCR) |
| **v3** | Integrasi API bank/open banking, multi-currency penuh, modul investasi sederhana |

---

## 12. Pertanyaan Terbuka (Untuk Didiskusikan)

1. Apakah aplikasi ini akan dipakai pribadi saja, atau nantinya dijadikan produk yang dijual juga ke klien (seperti BukuUntung)?
2. Apakah perlu integrasi dengan sistem akuntansi/Odoo yang sudah digunakan di Smart Inovasi?
3. Prioritas platform: web responsif (PWA) dulu, atau langsung native mobile app (Android/iOS)?
