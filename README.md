# Guyub Banyuwangi Network (GBN) Arisan System

Sistem Manajemen Arisan Warga GBN yang dirancang untuk menjadi aman, transparan, dan mudah digunakan. Aplikasi berbasis web ini memudahkan administrator dalam mengelola anggota, periode arisan, memvalidasi bukti transfer, hingga mencetak laporan.

![Dashboard Preview](assets/screenshot.png) _(Opsional: Tambahkan screenshot dashboard)_

## 🌟 Fitur Utama

- **Sistem Pembayaran Terpadu (Pending & Lunas)**: Otomatisasi validasi pembayaran anggota dengan fitur upload bukti transfer, dengan peran admin sebagai validator.
- **Periode Arisan Dinamis**: Periode arisan secara otomatis maju per 14 hari, dan setiap riwayat tarikan / kas terisolasi (period-scoped).
- **Notifikasi WhatsApp Otomatis**: Mudah mengirimkan struk pembayaran atau tagihan ke WA member langsung lewat sekali klik.
- **Kelola Pemenang (Tarikan)**: Fitur acak tarikan penerima arisan dengan catatan riwayat penerimaan.
- **Laporan & Ekspor Data**: Ekspor data pembayaran ke Excel/CSV lengkap dengan filter per tanggal.
- **Dashboard Dual-Role**: Tampilan dinamis (`dashboard.php` tunggal) yang dapat menyesuaikan informasi berdasarkan _role_ yang login (Admin vs User biasa).
- **Keamanan Lanjutan**: Mencegah celah _SQL Injection_ dengan 100% implementasi Eksekusi Statement Tersiapkan (_Prepared Statements_).

## 🛠️ Tech Stack & Persyaratan Sistem

- **PHP** (Disarankan versi 8.0 atau yang lebih baru)
- **MySQL / MariaDB** Server (Melalui XAMPP / Laragon / LAMP)
- Web Server (Apache / Nginx)
- Koneksi internet (Hanya untuk memuat CDN _SweetAlert2_, _Bootstrap 5_, dan notif _WhatsApp API_)

## 📦 Panduan Instalasi (Development Lokal)

1. Pastikan Anda telah menginstal **XAMPP / Laragon**.
2. _Clone_ atau unduh _source code_ ini, kemudian letakkan di dalam folder `htdocs` (jika memakai XAMPP):
   ```bash
   C:\xampp\htdocs\GBN
   ```
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) dan buat sebuah _database_ baru bernama `arisan`.
4. _Import_ tabel-tabel MySQL dengan memilih tab **Import** dan unggah file `arisan.sql` yang ada di `root` folder project.
5. Akses website melalui _browser_:
   ```url
   http://localhost/GBN
   ```

## 🔒 Akun Login Default

**Administrator:**

- Username: `admin`
- Password: `password` _(atau sesuai hashed password yang dikonfigurasi pada database Anda)_

**Anggota / User:**

- Anda dapat mendaftar atau menginput user secara manual dari menu "Kelola Anggota" maupun "Kelola Pengguna".

## 📁 Struktur Folder Utama

```text
/GBN
├── auth/                 # Logika login & logout
├── assets/               # CSS global & ikon
├── config/               # Pengaturan koneksi `koneksi.php`
├── partials/             # Elemen UI statis (Header, Footer, Navbar)
├── uploads/bukti/        # Tempat menyimpan gambar bukti transfer
├── user/                 # Kelola CRUD Pengguna
├── periode_detail.php    # Modul khusus manajemen Tarikan Pemenang
├── proses_bayar*.php     # Modul eksekusi pembayaran / upload admin & user
└── validasi_*.php        # Sistem validasi LUNAS oleh admin
```

## 📋 Catatan Pembaruan (Refactor)

Aplikasi ini baru saja disempurnakan (Mei/Maret 2026) dengan perubahan arsitektur utama:

1. **Keamanan Mutlak (100% Parameterized Query)**: Sistem tahan terhadap eksploitasi injeksi SQL.
2. **Perubahan Flow Transaksi**: _User_ tak bisa langsung LUNAS; wajib upload bukti untuk diperiksa admin terlebih dahulu.
3. Manajemen file **Masa Lalu (Legacy)** telah dibersihkan alias diubah menjadi URL redirect stub demi mencegah eksekusi program kedaluwarsa oleh skrip bot peretas.

---

_Hak Cipta &copy; 2026 Guyub Banyuwangi Network._
