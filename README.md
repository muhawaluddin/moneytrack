# MoneyTrack

Aplikasi pencatatan keuangan pribadi berbasis Laravel 13, dibuat dari `PRD keuangan pribadi.md`.

## Fitur

- Autentikasi dan isolasi data per pengguna
- Multi sumber kas dengan saldo real-time
- Pemasukan, pengeluaran, transfer, status pending, dan transaksi berulang
- Kategori/sub-kategori kustom dan 25 kategori bawaan
- Dashboard cashflow, tren enam bulan, top kategori, dan status anggaran
- Anggaran bulanan serta salin dari bulan sebelumnya
- Filter transaksi, laporan periode/akun, dan export CSV
- UI mobile-first, dark mode, manifest PWA, dan cache offline dasar
- Ruang pribadi dan keluarga dengan pemilih ruang di desktop
- Undangan pasangan berbasis token, role owner/manager/contributor
- Akun pribadi atau bersama dan atribusi pencatat transaksi
- Nomor rekening terenkripsi, password/PIN hashed, dan transaksi saldo atomik

## Menjalankan

Prasyarat: PHP 8.3+, Composer, dan ekstensi SQLite.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Buka `http://127.0.0.1:8000`, daftar, lalu tambahkan sumber kas pertama. Aset UI utama sudah tersedia di `public/app.css`, sehingga npm tidak diperlukan untuk menjalankan aplikasi.

## Ruang Keluarga

1. Buka menu **Keluarga**, lalu buat ruang keluarga.
2. Buat undangan menggunakan email pasangan dan bagikan tautan yang dihasilkan.
3. Pasangan login/daftar menggunakan email yang sama lalu membuka tautan tersebut.
4. Saat membuat sumber kas di ruang keluarga, pilih **Bersama** atau **Pribadi**.

Akun pribadi hanya terlihat pemiliknya. Akun bersama dapat dicatat oleh seluruh anggota, sementara edit transaksi anggota lain hanya tersedia bagi pemilik dan pengelola ruang.

## Verifikasi

```bash
php artisan test
vendor/bin/pint --test
```

Untuk produksi, ubah `APP_ENV=production`, `APP_DEBUG=false`, gunakan HTTPS, atur database/backup, lalu jalankan `php artisan optimize`.
