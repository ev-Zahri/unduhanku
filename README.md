# 📥 Unduhanku — Universal Social Media Downloader & Monetization Suite

Aplikasi **Universal Social Media Downloader** modern berbasis arsitektur **Hybrid Monorepo** yang menggabungkan antarmuka Glassmorphism responsif, engine ekstraksi Python performa tinggi, sistem direct streaming tanpa beban penyimpanan, dashboard analitik admin tersembunyi, serta manajemen monetisasi iklan afiliasi.

---

## 🌟 Fitur Utama

### 1. Multi-Platform Media Downloader
- **Platform yang Didukung:** TikTok, Instagram (Reels & Carousel), Facebook (Video HD/SD, Post Foto Tunggal & Album/Slide), Twitter/X, YouTube (Video & Audio), dan 1000+ situs lain berbasis engine `yt-dlp`.
- **Dukungan Media Campuran & Slide Carousel:** Deteksi cerdas untuk postingan berisi campuran video dan foto dalam satu tautan dengan pratinjau kartu per slide dan opsi **"Unduh Semua Slide"**.
- **Ekstraksi Audio MP3:** Konversi dan unduh audio berkualitas tinggi (128–192 kbps) langsung dari video.
- **Auto Clipboard Paste:** Tombol tempel instan dari clipboard dengan 1 klik.

### 2. Optimasi Penyimpanan & Bandwidth Server
- **Direct Progressive Streaming (`api/proxy-download`):** Format video yang sudah menggabungkan video dan audio (TikTok, Instagram, Twitter, Facebook progressive MP4) langsung dialirkan ke klien via cURL streaming tanpa disimpan ke harddisk server (**0 Byte Disk Write**).
- **Auto-Delete File Fisik Pasca Unduh:** Pada format yang memerlukan proses *merging* di server (YouTube), file fisik di `storage/downloads/` langsung **dihapus otomatis seketika** setelah byte terakhir berhasil diterima browser klien via PHP `register_shutdown_function`.
- **Cron Job Pemeliharaan Mandiri (`bin/cleanup.php`):** Skrip CLI untuk membersihkan berkas unduhan usang (>30 menit), file bulk temporary (>1 jam), dan memangkas riwayat database lama (>60 hari) disertai kompresi otomatis SQLite `VACUUM`.

### 3. Dashboard Admin & Analitik Tersembunyi
- **Path URL Rahasia & Autentikasi:** Akses terbatas melalui URL khusus (misal `/secret-adm-console`) dengan proteksi sesi login admin.
- **Halaman Analitik:**
  - Grafik interaktif dengan 3 rentang waktu: **Mingguan**, **Bulanan**, dan **Tahunan**.
  - Tabel riwayat unduhan dengan paginasi (limit 10), menampilkan alamat IP klien, platform sumber, ukuran file, tanggal, dan tautan video asli.
- **Halaman Monitor DB & Logs:**
  - Metrik kesehatan database SQLite (ukuran fisik file, total baris, tanggal rekam pertama & terakhir).
  - Fitur pembersihan riwayat berbasis rentang tanggal atau hapus semua dengan otomatisasi `VACUUM`.
  - Penjelajah dan pengunduh file catatan log server (`logs/error-*.log`).

### 4. Manajemen Iklan Afiliasi (Monetization Suite)
- **Manajemen Iklan di Admin Dashboard:**
  - Tambah iklan manual: Nama Produk, Komisi (Rp), Link Produk, dan Link Komisi Ekstra.
  - Upload Iklan Massal (Bulk CSV/Excel): Dilengkapi fitur **Live Preview** dan **Pemetaan Kolom Interaktif** sebelum disimpan ke database.
  - Tabel Iklan Terbaik (Paginasi limit 10) dengan kolom nama, komisi, tautan dipersingkat (maks 20 karakter), jumlah klik, potensi isu, dan tombol toggle penayangan aktif.
- **Penayangan Cerdas di Sisi Klien:**
  - Iklan terbuka di tab baru secara otomatis ketika klien menekan tombol Tempel, Analisis, atau Unduh media.
  - **Pembatasan Kuota:** Iklan tayang maksimal 3x per hari per klien (terproteksi di `localStorage`).
  - **Algoritma Prioritas Tayang:** Mengutamakan iklan aktif dengan jumlah klik terendah agar distribusi tayangan merata.

### 5. UI/UX Modern & SEO Friendly
- Tema Minimalist Dark Glassmorphism dengan tipografi Google Fonts (Outfit & Inter).
- **Desain Responsif Penuh:**
  - **Ponsel:** Optimal pada lebar 480px.
  - **Tablet:** Optimal pada lebar 768px.
  - **Desktop:** Tata letak konten lebar terpusat.
- Optimasi SEO lengkap: Tag Title dinamis, Deskripsi Meta, Open Graph (Facebook), Twitter Cards, `robots.txt`, dan `sitemap.xml`.

---

## 📂 Struktur Direktori Proyek

```
unduhanku/
├── .env                          # Konfigurasi lingkungan & kredensial admin
├── .env.example                  # Template variabel lingkungan
├── composer.json                 # Konfigurasi dependensi PHP & PSR-4 Autoloading
├── sample-iklan.csv              # Contoh format CSV untuk impor iklan massal
│
├── bin/
│   └── cleanup.php               # CLI maintenance script untuk Cron Job server
│
├── engine/                       # Python Downloader Engine
│   ├── extractor.py              # Engine ekstraktor media berbasis yt-dlp & custom parser
│   └── requirements.txt          # Dependensi Python (yt-dlp)
│
├── database/
│   └── setup.sql                 # Skema referensi database
│
├── logs/                         # Folder catatan log server
│   └── error-YYYY-MM-DD.log      # Log error harian dengan ID Referensi unik
│
├── public/                       # Web Root Server
│   ├── .htaccess                 # Konfigurasi rewrite rule Apache
│   ├── index.php                 # Entry point aplikasi, router API & view handler
│   ├── index.html                # Antarmuka web utama (Glassmorphism UI)
│   ├── robots.txt                # Konfigurasi crawler mesin pencari
│   ├── sitemap.xml               # Peta situs untuk pengindeksan SEO
│   ├── css/
│   │   └── style.css             # Desain styling dark mode & responsive queries
│   └── js/
│       └── app.js                # Logika klien: analisis, streaming, ad trigger, & UI states
│
├── src/                          # PHP Application Core (Namespace: App\)
│   ├── Router.php                # Engine router URL ringan (GET & POST)
│   ├── Controllers/
│   │   ├── AdminController.php   # Controller dashboard, auth, analitik, DB, & iklan
│   │   └── MediaController.php   # Controller API analisis, direct streaming, & serve file
│   └── Services/
│       ├── AdvertisementService.php # Layanan CRUD iklan, bulk import CSV, & click tracker
│       ├── DownloaderService.php    # Bridge eksekusi engine Python via proc_open aman
│       ├── HistoryService.php       # Penyimpanan riwayat & analitik berbasis SQLite
│       └── LoggerService.php        # Pencatatan error terstruktur dengan referensi acak
│
└── storage/                      # Folder Penyimpanan Lokal
    ├── downloads/                # Folder temporer unduhan server
    └── history.sqlite            # Database SQLite lokal (riwayat unduhan & iklan)
```

---

## ⚙️ Kebutuhan Sistem (Prerequisites)

1. **PHP:** Versi 8.0 atau lebih tinggi.
   - Ekstensi wajib: `pdo_sqlite`, `curl`, `mbstring`, `openssl`.
2. **Python:** Versi 3.8 atau lebih tinggi.
   - Pustaka Python: `yt-dlp` (instalasi: `pip install -r engine/requirements.txt` atau `pip install yt-dlp`).
3. **FFmpeg (Opsional namun disarankan):** Untuk penggabungan video 1080p+ YouTube dan konversi audio MP3 kualitas tinggi.
4. **Composer:** Untuk instalasi dependensi PHP (opsional, aplikasi memiliki fallback native parser).

---

## 🚀 Panduan Instalasi & Menjalankan Lokal

### 1. Salin Proyek & Konfigurasi Lingkungan
Letakkan folder proyek di dalam web root (misal `d:\xampp\htdocs\unduhanku`). Salin file konfigurasi:
```bash
cp .env.example .env
```
Buka `.env` dan sesuaikan nilainya:
```ini
APP_ENV=development
APP_DEBUG=true

# Lokasi eksekutor Python di komputer Anda
PYTHON_BIN="C:\Users\Username\AppData\Local\Programs\Python\Python310\python.exe"

# Konfigurasi Kredensial & URL Admin
ADMIN_PATH=/secret-adm-console
ADMIN_USERNAME=xxxx
ADMIN_PASSWORD=xxxx
```

### 2. Pasang Dependensi
```bash
# Dependensi PHP
composer install

# Dependensi Engine Python
pip install -r engine/requirements.txt
```

### 3. Menjalankan Server Lokal

#### Opsi A: Menggunakan XAMPP (Apache)
1. Aktifkan modul **Apache** melalui XAMPP Control Panel.
2. Buka browser dan kunjungi:
   - Web Klien: `http://localhost/unduhanku/public/`
   - Dashboard Admin: `http://localhost/unduhanku/public/secret-adm-console`

#### Opsi B: Menggunakan PHP Built-in Server
Jalankan perintah berikut di terminal root proyek:
```bash
php -S localhost:8000 -t public
```
Akses melalui browser di:
- Web Klien: `http://localhost:8000/`
- Dashboard Admin: `http://localhost:8000/secret-adm-console`

---

## 📡 Dokumentasi Endpoint REST API

### Publik / Klien
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `POST` | `/api/analyze` | Menganalisis URL sosial media dan mengembalikan daftar resolusi, thumbnail, judul, & slide carousel. |
| `POST` | `/api/download` | Memulai pengunduhan media via server-side engine (YouTube/Merge). |
| `GET` | `/api/proxy-download` | Streaming langsung media progresif dari CDN ke browser klien tanpa membebani disk server. |
| `GET` | `/api/file?name=...` | Mengalirkan file fisik dari server ke klien (otomatis dihapus setelah terkirim). |
| `GET` | `/api/history` | Mengambil 15 riwayat unduhan terakhir. |
| `GET` | `/api/status` | Mengecek status kesehatan server, versi PHP, Python, dan engine `yt-dlp`. |
| `GET` | `/api/ad/serve` | Mengambil 1 iklan afiliasi aktif dengan prioritas klik terendah. |
| `POST` | `/api/ad/click` | Mencatat hit klik pada iklan tertentu. |

### Admin Dashboard (Memerlukan Sesi Login)
| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `POST` | `/api/admin/login` | Autentikasi akun admin. |
| `POST` | `/api/admin/logout` | Mengakhiri sesi login admin. |
| `GET` | `/api/admin/analytics?period=...` | Statistik grafik (`weekly`, `monthly`, `yearly`) dan total unduhan. |
| `GET` | `/api/admin/history?page=...` | Daftar riwayat unduhan dengan paginasi 10 data per halaman. |
| `GET` | `/api/admin/db-stats` | Informasi statistik fisik file SQLite dan rentang data. |
| `POST` | `/api/admin/history/delete` | Menghapus riwayat berdasarkan rentang tanggal atau seluruhnya + `VACUUM`. |
| `GET` | `/api/admin/logs` | Daftar file log error server di folder `logs/`. |
| `GET` | `/api/admin/logs/download?file=...` | Mengunduh file log server tertentu. |
| `GET` | `/api/admin/ads?page=...` | Daftar tabel iklan afiliasi teratas dengan metrik klik & status. |
| `POST` | `/api/admin/ads/create` | Menambahkan data iklan baru secara manual. |
| `POST` | `/api/admin/ads/toggle` | Mengubah penayangan aktif antara Link Produk atau Komisi Ekstra. |
| `POST` | `/api/admin/ads/delete` | Menghapus iklan dari database. |
| `POST` | `/api/admin/ads/bulk-preview` | Mengunggah CSV/Excel sementara untuk live preview & pemetaan kolom. |
| `POST` | `/api/admin/ads/bulk-import` | Mengeksekusi impor data massal berdasarkan kolom yang dipetakan. |

---

## ⏰ Konfigurasi Cron Job di VPS Linux

Untuk menjaga performa VPS dan mencegah penumpukan file sementara atau membengkaknya database, pasang pemeliharaan berkala pada crontab server:

1. Buka konfigurasi cron:
   ```bash
   crontab -e
   ```
2. Tambahkan baris pemeliharaan otomatis setiap jam:
   ```cron
   # Eksekusi pembersihan file unduhan usang & vacuum database SQLite setiap jam
   0 * * * * /usr/bin/php /var/www/unduhanku/bin/cleanup.php >> /var/www/unduhanku/logs/cron.log 2>&1
   ```
3. Uji coba skrip pemeliharaan secara manual dari terminal:
   ```bash
   # Pembersihan langsung
   php bin/cleanup.php

   # Mode simulasi (dry-run) tanpa menghapus file:
   php bin/cleanup.php --dry-run

   # Kustomisasi batas usia berkas (15 menit) & retensi riwayat (30 hari):
   php bin/cleanup.php --days=30 --max-age=15
   ```

---

## 🛡️ Keamanan & Penanganan Error

- **Zero Information Leakage:** Kesalahan fatal atau notice PHP tidak pernah dibocorkan langsung ke antarmuka pengguna.
- **Reference Error ID:** Setiap kegagalan sistem menghasilkan kode referensi unik (contoh: `ERR-4D64A332`) yang dicatat lengkap beserta *stack trace* dan *context payload* ke dalam file harian di `logs/error-YYYY-MM-DD.log`.
- **Sanitasi File & Parameter:** Semua nama file hasil unduhan dibersihkan dari karakter berbahaya sebelum dialirkan ke klien.
- **Proteksi Admin URL:** URL admin bersifat dinamis dan dapat diganti kapan saja melalui variabel lingkungan `.env`.

---

## 📄 Lisensi

Proyek ini dikembangkan secara independen untuk keperluan pengunduhan konten sosial media publik secara legal dan bertanggung jawab. Hak cipta konten media tetap menjadi milik kreator asli di masing-masing platform sosial media.
