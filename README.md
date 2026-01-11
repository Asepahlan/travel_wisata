# Sistem Pemesanan Travel Wisata

## Deskripsi Project
Sistem Pemesanan Travel Wisata adalah aplikasi web yang memudahkan pengguna untuk memesan jasa transportasi travel wisata secara online. Aplikasi ini menyediakan berbagai pilihan paket perjalanan dengan rute dan harga yang bervariasi, serta memungkinkan pengguna untuk melakukan pemesanan secara mandiri melalui antarmuka yang user-friendly.

## Tujuan Project
1. Menyediakan platform online untuk pemesanan jasa travel wisata
2. Mempermudah pengguna dalam membandingkan dan memilih paket perjalanan
3. Meningkatkan efisiensi manajemen pemesanan dan pengelolaan armada
4. Memberikan pengalaman pemesanan yang cepat, mudah, dan transparan

## Fitur Aplikasi

### Fitur Pengguna
- Pencarian dan pemilihan paket travel
- Form pemesanan online
- Pengecekan status pemesanan
- Informasi detail armada dan rute
- Konfirmasi pemesanan via WhatsApp
- Cetak bukti pemesanan (PDF)

### Fitur Admin
- Manajemen data paket perjalanan
- Manajemen armada kendaraan
- Manajemen supir
- Monitoring pemesanan
- Laporan dan statistik
- Manajemen pengguna admin
- Export data ke Excel/PDF

## Alur Kerja Sistem
1. Pengguna mengunjungi website dan melihat daftar paket perjalanan
2. Pengguna memilih paket dan mengisi form pemesanan
3. Sistem memproses pemesanan dan mengirim konfirmasi
4. Admin menerima notifikasi pemesanan baru
5. Admin memverifikasi dan mengkonfirmasi ketersediaan
6. Pengguna melakukan pembayaran dan mengunggah bukti transfer
7. Admin memvalidasi pembayaran dan mengupdate status pemesanan
8. Perjalanan berlangsung sesuai jadwal
9. Setelah perjalanan selesai, status diupdate menjadi selesai

## Teknologi yang Digunakan

### Frontend
- HTML5
- CSS3 (dengan Tailwind CSS)
- JavaScript (Vanilla)
- Font Awesome untuk ikon

### Backend
- PHP 8.2+
- MySQL/MariaDB
- PDO untuk koneksi database

### Tools & Library
- Composer (untuk manajemen dependensi)
- PHPMailer (untuk pengiriman email)
- FPDF (untuk generate PDF)

## Struktur Project
```
travel-wisata/
├── admin/                  # Halaman admin
│   ├── includes/           # File include untuk admin
│   ├── partials/           # Komponen UI admin
│   ├── booking-detail.php  # Detail pemesanan
│   ├── bookings.php        # Manajemen pemesanan
│   ├── drivers.php         # Manajemen supir
│   ├── fleets.php          # Manajemen armada
│   └── ...
├── assets/                 # Aset seperti gambar, CSS, JS
│   └── images/
├── config/                 # Konfigurasi database dan aplikasi
│   ├── config.php
│   └── database.php
├── helpers/                # Fungsi-fungsi bantu
│   └── functions.php
├── partials/               # Komponen UI frontend
│   ├── footer.php
│   ├── header.php
│   └── navbar.php
├── cek-status.php          # Halaman pengecekan status
├── index.php               # Halaman utama
├── paket.php               # Daftar paket
├── pesan.php               # Form pemesanan
└── sukses.php              # Halaman sukses pemesanan
```

## Database
Database menggunakan MySQL/MariaDB dengan beberapa tabel utama:
- `admin` - Data admin sistem
- `paket` - Daftar paket perjalanan
- `rute` - Rute perjalanan
- `pemesanan` - Data pemesanan
- `supir` - Data supir
- `armada` - Data armada kendaraan

## Instalasi

### Persyaratan
- PHP 8.2 atau lebih baru
- MySQL/MariaDB
- Web server (Apache/Nginx)
- Composer (untuk dependensi)

### Langkah-langkah
1. Clone repository ini
2. Buat database baru
3. Import file `travel_wisata.sql` ke database yang telah dibuat
4. Salin `.env.example` ke `.env` dan sesuaikan konfigurasi database
5. Install dependensi:
   ```
   composer install
   ```
6. Pastikan folder `assets` memiliki izin tulis
7. Akses aplikasi melalui web server

## Konfigurasi
File konfigurasi utama berada di `config/config.php`. Beberapa pengaturan yang dapat disesuaikan:
- Koneksi database
- Nama website
- Informasi kontak
- Pengaturan email
- Konfigurasi WhatsApp

## Penggunaan
1. **Halaman Depan**
   - Lihat daftar paket perjalanan
   - Pilih paket dan isi form pemesanan
   - Cetak bukti pemesanan

2. **Halaman Admin**
   - Login menggunakan kredensial admin
   - Kelola paket, rute, dan armada
   - Lihat laporan pemesanan

## Keamanan
- Menggunakan prepared statements untuk mencegah SQL injection
- Validasi input form
- Sistem autentikasi untuk admin
- Proteksi CSRF

## Kontribusi
Kontribusi dipersilakan! Silakan buat pull request dengan perubahan yang diusulkan.

## Lisensi
Proyek ini dilisensikan di bawah [MIT License](LICENSE).
