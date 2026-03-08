# Pengumuman Penerimaan Karyawan (PHP Native)

## Fitur
- Landing page user: tampil pengumuman + tabel peserta
- Admin panel: login, tambah pengumuman, publish/unpublish, kelola peserta (bulk max 50), hapus
- Konfirmasi via WhatsApp admin (nomor bisa diatur di menu Setting)

## Setup (XAMPP macOS)
1. Buat database `pengumuman` di phpMyAdmin.
2. Import file SQL: `sql/schema.sql`.
3. Pastikan konfigurasi DB benar di `inc/config.php`.
4. (Opsional) Folder `uploads/` tidak digunakan lagi.

## Kredensial Admin Default
- Username: `admin`
- Password: `Admin@12345`

> Setelah login, sebaiknya ganti password (belum dibuat UI ganti password; bisa diubah langsung di DB).

## URL
- User landing: `http://localhost/pengumuman/`
- Admin login: `http://localhost/pengumuman/admin/login.php`

## Setting WhatsApp
- Buka: `http://localhost/pengumuman/admin/settings.php`
- Isi Nomor WhatsApp admin (contoh: `08123456789` / `+628123456789` / `628123456789`).
# pengumuman-
