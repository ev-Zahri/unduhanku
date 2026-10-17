# 📥 Unduhanku — Universal Social Media Downloader (Monorepo)

Aplikasi **Universal Social Media Downloader** berbasis arsitektur **Hybrid Monorepo** yang menggabungkan:
- **Frontend:** Modern Web UI Glassmorphism (HTML5, Vanilla CSS & Modern JS)
- **Backend API:** PHP 8+ dengan Router MVC ringan & PSR-4 Autoloading
- **Engine Core:** Python 3 + `yt-dlp` untuk ekstraksi & pengunduhan dari 1000+ platform (YouTube, TikTok, Instagram, Twitter/X, Facebook, dll)
- **Storage & Database:** SQLite lokal otomatis (`storage/history.sqlite`) tanpa perlu konfigurasi MySQL manual

---

## 📂 Struktur Monorepo

```
unduhanku/
├── .env                          # Konfigurasi aplikasi & path eksekutor Python
├── .env.example
├── composer.json                 # Dependency PHP & PSR-4 autoload mapping
├── .gitignore
│
├── engine/                       # Python Downloader Engine
│   ├── extractor.py              # CLI extractor berbasis yt-dlp (output JSON)
│   └── requirements.txt          # Dependensi Python (yt-dlp)
│
├── src/                          # PHP Application Layer (App\ namespace)
│   ├── Controllers/
│   │   └── MediaController.php   # Endpoint /api/analyze, /api/download, /api/file, /api/history
│   ├── Services/
│   │   ├── DownloaderService.php # Bridge eksekusi engine Python via proc_open
│   │   └── HistoryService.php    # Pencatatan riwayat unduhan berbasis SQLite
│   └── Router.php                # Router dinamis (mendukung Apache XAMPP & CLI server)
│
├── public/                       # Public Web Root
│   ├── .htaccess                 # Rewrite rule Apache
│   ├── index.php                 # Entry point PHP & routing API
│   ├── index.html                # Antarmuka web modern & responsif
│   ├── css/
│   │   └── style.css             # Glassmorphism dark UI
│   └── js/
│       └── app.js                # Interaktivitas clipboard, parsing, dan AJAX
│
└── storage/                      # Penyimpanan File & Cache
    ├── downloads/                # Folder tempat file hasil unduhan disimpan sementara
    └── history.sqlite            # Database SQLite riwayat unduhan
```

---

## 🚀 Cara Menjalankan Aplikasi

### Opsi 1: Lewat Apache / XAMPP (Rekomendasi)
1. Pastikan Apache di XAMPP Control Panel sudah **Start**.
2. Buka browser dan akses salah satu URL berikut:
   ```
   http://localhost/unduhanku/public
   ```
   *(atau `http://localhost/zuzu/public`)*

### Opsi 2: Menggunakan PHP Built-in Server (Terminal)
Buka terminal di folder proyek (`d:\xampp\htdocs\unduhanku`) lalu jalankan:
```powershell
php -S localhost:8000 -t public
```
Lalu buka browser di:
```
http://localhost:8000
```

---

## 🛠️ Fitur Unggulan

1. **Auto Clipboard Paste:** Tombol tempel langsung membaca URL dari clipboard dengan 1 klik.
2. **Multi-Format Extraction:**
   - **Video:** Pilihan resolusi dinamis (1080p, 720p, 480p, 360p).
   - **Audio:** Ekstraksi audio murni MP3.
   - **Gallery/Images:** Mendukung unduhan postingan slide foto / Instagram carousel.
3. **Riwayat Unduhan:** Tersimpan di drawer samping dengan akses unduh ulang instan.
4. **Endpoint REST API:**
   - `POST /api/analyze` — Analisis metadata URL & list format
   - `POST /api/download` — Unduh media ke server
   - `GET  /api/file?name=...` — Streaming file ke perangkat pengguna
   - `GET  /api/history` — Riwayat 15 unduhan terakhir
   - `GET  /api/status` — Status kesehatan engine & versi
