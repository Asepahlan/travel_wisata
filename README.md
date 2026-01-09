# Travel Wisata - Sistem Manajemen Biro Perjalanan

## Deskripsi
Travel Wisata adalah sebuah sistem manajemen biro perjalanan yang memungkinkan pengguna untuk memesan paket perjalanan secara online. Sistem ini terdiri dari dua bagian utama:
- Frontend untuk pelanggan (pemesanan paket wisata)
- Backend admin untuk manajemen pemesanan, paket, dan armada

## Fitur Utama

### Untuk Pengguna
- Melihat daftar paket perjalanan
- Melakukan pemesanan online
- Melacak status pemesanan
- Mencetak bukti pembayaran

### Untuk Admin
- Manajemen paket perjalanan
- Manajemen rute
- Manajemen armada dan supir
- Konfirmasi pembayaran
- Laporan pemesanan
- Manajemen pengguna admin

## Teknologi yang Digunakan
- PHP 8.2+
- MySQL/MariaDB
- HTML5, CSS3, JavaScript
- Tailwind CSS untuk styling
- FPDF untuk generate PDF
- PDO untuk koneksi database

## Struktur Direktori
```
.
├── admin/              # Halaman admin
├── assets/             # Aset seperti gambar, CSS, JS
├── config/             # File konfigurasi
├── helpers/            # Helper functions
├── partials/           # Komponen yang bisa digunakan ulang
├── process/            # File pemrosesan form
├── vendor/             # Dependensi PHP
├── cek-status.php      # Halaman cek status pemesanan
├── generate_pdf.php    # Generate PDF untuk bukti pemesanan
├── index.php           # Halaman utama
├── paket.php          # Daftar paket perjalanan
├── pesan.php          # Form pemesanan
└── sukses.php         # Halaman sukses pemesanan
```

## Database
Database menggunakan MySQL/MariaDB dengan beberapa tabel utama:
- `admin` - Data admin sistem
- `paket` - Daftar paket perjalanan
- `rute` - Rute perjalanan
- `pemesanan` - Data pemesanan
- `supir` - Data supir
- `armada` - Data armada kendaraan
- `pembayaran` - Data pembayaran

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
   - Lakukan pembayaran
   - Cetak bukti pemesanan

2. **Halaman Admin**
   - Login menggunakan kredensial admin
   - Kelola paket, rute, dan armada
   - Konfirmasi pembayaran
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

## Kontak
Untuk informasi lebih lanjut, silakan hubungi:
- Email: info@travelwisata.com
- Telepon: 085798347675
- Alamat: G723+473, Jalan, Mandalawangi, Kec. Salopa, Kabupaten Tasikmalaya, Jawa Barat
