# Sistem Manajemen Travel Wisata

## Deskripsi
Sistem Manajemen Travel Wisata adalah aplikasi berbasis web yang dirancang untuk memfasilitasi pengelolaan layanan travel, termasuk pemesanan kendaraan, manajemen armada, dan pelacakan perjalanan. Sistem ini terdiri dari dua antarmuka utama: halaman pengguna untuk pemesanan dan panel admin untuk manajemen.

## Fitur Utama

### Pengguna
- Melihat daftar paket perjalanan
- Melakukan pemesanan kendaraan
- Melacak status pemesanan
- Mencetak bukti pemesanan (PDF)
- Konfirmasi pemesanan melalui WhatsApp

### Admin
- Manajemen data armada kendaraan
- Pengelolaan rute perjalanan
- Manajemen pemesanan
- Laporan dan statistik
- Manajemen pengguna admin
- Ekspor data ke Excel/PDF

## Teknologi yang Digunakan

### Frontend
- HTML5
- CSS3 dengan Tailwind CSS
- JavaScript (ES6+)
- Font Awesome untuk ikon

### Backend
- PHP 8.2+
- MySQL/MariaDB
- PDO untuk koneksi database

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
1. Clone repositori ini ke direktori web server
2. Buat database baru dan import file `travel_wisata.sql`
3. Salin `config/config.example.php` menjadi `config/config.php`
4. Sesuaikan konfigurasi database di `config/config.php`
5. Pastikan direktori `uploads/` memiliki izin tulis
6. Akses aplikasi melalui browser

## Persyaratan Sistem

### Server
- PHP 7.4 atau lebih baru
- MySQL 5.7 atau lebih baru
- Web server (Apache/Nginx)
- Ekstensi PHP: PDO, MySQLi, JSON, cURL

### Browser
- Google Chrome versi terbaru
- Mozilla Firefox versi terbaru
- Microsoft Edge versi terbaru

## Catatan Penting

### Pemeriksaan Menyeluruh
Sebelum digunakan di lingkungan produksi, lakukan pemeriksaan menyeluruh terhadap seluruh fitur sistem untuk memastikan kompatibilitas dengan kebutuhan Anda.

### Proses Pembayaran
- Sistem TIDAK memiliki fitur pembayaran otomatis
- Proses pembayaran dilakukan di luar sistem
- Konfirmasi dan verifikasi pembayaran dilakukan melalui WhatsApp

### Keamanan
- Selalu ubah kredensial default setelah instalasi
- Lakukan backup database secara berkala
- Update sistem secara berkala untuk keamanan yang lebih baik

## Login Admin Default
- **Username**: admin
- **Password**: admin123

*Harap segera ganti password default setelah login pertama kali*

## Lisensi
Proyek ini dilisensikan di bawah [MIT License](LICENSE).

## Dukungan
Untuk bantuan lebih lanjut, silakan hubungi tim pengembang.

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
