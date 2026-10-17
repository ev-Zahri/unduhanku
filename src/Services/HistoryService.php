<?php

/**
 * HistoryService.php
 * 
 * Mengelola penyimpanan riwayat unduhan di SQLite (storage/history.sqlite)
 * Mendukung pencatatan IP, analitik (mingguan, bulanan, tahunan),
 * pagination limit 10, dan pemeliharaan/pembersihan database.
 */

namespace App\Services;

use PDO;
use PDOException;

class HistoryService
{
    private ?PDO $db = null;
    private string $dbPath;

    public function __construct()
    {
        $rootDir = dirname(__DIR__, 2);
        $storageDir = $rootDir . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $this->dbPath = $storageDir . DIRECTORY_SEPARATOR . 'history.sqlite';

        try {
            $this->db = new PDO("sqlite:{$this->dbPath}");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Buat tabel download_history jika belum ada
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS download_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    url TEXT NOT NULL,
                    platform TEXT,
                    filename TEXT,
                    filesize INTEGER DEFAULT 0,
                    thumbnail TEXT,
                    ip_address TEXT DEFAULT '127.0.0.1',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Pastikan kolom ip_address ada (migrasi otomatis untuk database yang sudah ada)
            $this->ensureIpColumn();

            // Seed sample data jika database baru/kosong agar analitik langsung memiliki visualisasi
            $this->seedInitialDataIfEmpty();
        } catch (PDOException $e) {
            $this->db = null;
        }
    }

    /**
     * Memastikan kolom ip_address ada pada tabel
     */
    private function ensureIpColumn(): void
    {
        if (!$this->db) return;
        try {
            $stmt = $this->db->query("PRAGMA table_info(download_history)");
            $cols = $stmt->fetchAll();
            $hasIp = false;
            foreach ($cols as $col) {
                if (($col['name'] ?? '') === 'ip_address') {
                    $hasIp = true;
                    break;
                }
            }
            if (!$hasIp) {
                $this->db->exec("ALTER TABLE download_history ADD COLUMN ip_address TEXT DEFAULT '127.0.0.1'");
            }
        } catch (\Throwable $e) {
            // Abaikan
        }
    }

    /**
     * Seed sample data realistis jika DB masih kosong
     */
    private function seedInitialDataIfEmpty(): void
    {
        if (!$this->db) return;
        try {
            $count = (int) $this->db->query("SELECT COUNT(*) FROM download_history")->fetchColumn();
            if ($count === 0) {
                $samples = [
                    ['title' => 'Video Tips Koding Modern Web', 'url' => 'https://www.tiktok.com/@creator/video/7391823901', 'platform' => 'TikTok', 'filename' => 'tiktok_tips_koding.mp4', 'filesize' => 14250000, 'ip_address' => '192.168.1.105', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hours'))],
                    ['title' => 'Reels Fotografi Sunset Bali', 'url' => 'https://www.instagram.com/reel/C89abXyZ/', 'platform' => 'Instagram', 'filename' => 'ig_sunset_bali.mp4', 'filesize' => 28900000, 'ip_address' => '180.244.130.45', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
                    ['title' => 'Highlight Sepakbola Liga Champions', 'url' => 'https://x.com/sports/status/1839201948', 'platform' => 'Twitter/X', 'filename' => 'x_ucl_highlight.mp4', 'filesize' => 34500000, 'ip_address' => '114.122.20.78', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 days'))],
                    ['title' => 'Tutorial Belajar Python Dasar', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'platform' => 'YouTube', 'filename' => 'yt_python_dasar.mp4', 'filesize' => 52100000, 'ip_address' => '36.85.12.190', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
                    ['title' => 'Resep Masakan Rumahan Praktis', 'url' => 'https://www.tiktok.com/@cooking/video/7390192831', 'platform' => 'TikTok', 'filename' => 'tiktok_resep.mp4', 'filesize' => 9800000, 'ip_address' => '125.160.88.12', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
                    ['title' => 'Album Foto Arsitektur Kota Tokyo', 'url' => 'https://www.instagram.com/p/C720xyzTok/', 'platform' => 'Instagram', 'filename' => 'ig_album_tokyo.jpg', 'filesize' => 4500000, 'ip_address' => '182.253.40.91', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
                    ['title' => 'Podcast Wirausaha & Bisnis Digital', 'url' => 'https://www.youtube.com/watch?v=k9X8aBcDeFg', 'platform' => 'YouTube', 'filename' => 'yt_podcast_bisnis.mp3', 'filesize' => 41200000, 'ip_address' => '103.28.14.50', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
                    ['title' => 'Desain Minimalist Interior Kamar', 'url' => 'https://x.com/interior/status/1820192847', 'platform' => 'Twitter/X', 'filename' => 'x_interior_design.jpg', 'filesize' => 3200000, 'ip_address' => '139.192.8.210', 'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))],
                    ['title' => 'Review Laptop Ultrabook Tipis', 'url' => 'https://www.youtube.com/watch?v=vB8x1L9oP0Q', 'platform' => 'YouTube', 'filename' => 'yt_review_laptop.mp4', 'filesize' => 78400000, 'ip_address' => '180.252.19.88', 'created_at' => date('Y-m-d H:i:s', strtotime('-8 days'))],
                    ['title' => 'Tutorial Dance Trend Populer', 'url' => 'https://www.tiktok.com/@dance/video/7381928374', 'platform' => 'TikTok', 'filename' => 'tiktok_dance.mp4', 'filesize' => 12400000, 'ip_address' => '114.79.44.102', 'created_at' => date('Y-m-d H:i:s', strtotime('-12 days'))],
                    ['title' => 'Koleksi Wallpapers Estetik 4K', 'url' => 'https://www.instagram.com/p/C690abXyWp/', 'platform' => 'Instagram', 'filename' => 'ig_wallpapers.jpg', 'filesize' => 6100000, 'ip_address' => '110.138.65.14', 'created_at' => date('Y-m-d H:i:s', strtotime('-18 days'))],
                    ['title' => 'Berita Teknologi & AI Terbaru', 'url' => 'https://x.com/techfeed/status/1810293847', 'platform' => 'Twitter/X', 'filename' => 'x_ai_news.mp4', 'filesize' => 16500000, 'ip_address' => '182.1.200.75', 'created_at' => date('Y-m-d H:i:s', strtotime('-25 days'))],
                ];

                $stmt = $this->db->prepare("
                    INSERT INTO download_history (title, url, platform, filename, filesize, thumbnail, ip_address, created_at)
                    VALUES (:title, :url, :platform, :filename, :filesize, '', :ip_address, :created_at)
                ");

                foreach ($samples as $s) {
                    $stmt->execute([
                        ':title'      => $s['title'],
                        ':url'        => $s['url'],
                        ':platform'   => $s['platform'],
                        ':filename'   => $s['filename'],
                        ':filesize'   => $s['filesize'],
                        ':ip_address' => $s['ip_address'],
                        ':created_at' => $s['created_at'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan
        }
    }

    /**
     * Catat unduhan baru ke riwayat
     */
    public function log(array $data): void
    {
        if (!$this->db) return;

        try {
            $stmt = $this->db->prepare("
                INSERT INTO download_history (title, url, platform, filename, filesize, thumbnail, ip_address, created_at)
                VALUES (:title, :url, :platform, :filename, :filesize, :thumbnail, :ip_address, :created_at)
            ");
            $stmt->execute([
                ':title'      => $data['title'] ?? 'Unduhan',
                ':url'        => $data['url'] ?? '',
                ':platform'   => $data['platform'] ?? 'Web',
                ':filename'   => $data['filename'] ?? '',
                ':filesize'   => (int) ($data['filesize'] ?? 0),
                ':thumbnail'  => $data['thumbnail'] ?? '',
                ':ip_address' => $data['ip_address'] ?? '127.0.0.1',
                ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // Silently fail agar proses download pengguna tetap sukses
        }
    }

    /**
     * Ambil riwayat unduhan dengan paginasi (Limit 10 untuk tabel analitik admin)
     * Kolom: IP address, link video (notasikan dengan "link"), sumber (platform), ukuran
     */
    public function getPaginated(int $page = 1, int $limit = 10): array
    {
        if (!$this->db) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 1];
        }

        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        try {
            $total = (int) $this->db->query("SELECT COUNT(*) FROM download_history")->fetchColumn();
            $totalPages = max(1, (int) ceil($total / $limit));

            $stmt = $this->db->prepare("
                SELECT id, title, url, platform, filename, filesize, ip_address, created_at
                FROM download_history
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'id'           => (int) $row['id'],
                    'ip_address'   => $row['ip_address'] ?: '127.0.0.1',
                    'url'          => $row['url'],
                    'title'        => $row['title'],
                    'sumber'       => $row['platform'] ?: 'Web',
                    'ukuran'       => (int) ($row['filesize'] ?? 0),
                    'ukuran_fmt'   => $this->formatBytes((int) ($row['filesize'] ?? 0)),
                    'waktu'        => $row['created_at'],
                    'waktu_fmt'    => date('d M Y, H:i', strtotime($row['created_at'])),
                ];
            }

            return [
                'items'       => $items,
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $totalPages,
            ];
        } catch (PDOException $e) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 1];
        }
    }

    /**
     * Dapatkan data analitik untuk grafik (Mingguan, Bulanan, Tahunan) dan KPI Ringkasan
     */
    public function getAnalytics(string $range = 'weekly'): array
    {
        if (!$this->db) {
            return [
                'chart'   => ['labels' => [], 'counts' => [], 'sizes_mb' => []],
                'summary' => [
                    'total_downloads' => 0,
                    'today_downloads' => 0,
                    'total_bytes'     => 0,
                    'total_bytes_fmt' => '0 B',
                    'top_platform'    => '-',
                ]
            ];
        }

        // 1. Ringkasan Keseluruhan
        $totalDownloads = (int) $this->db->query("SELECT COUNT(*) FROM download_history")->fetchColumn();
        $todayDownloads = (int) $this->db->query("SELECT COUNT(*) FROM download_history WHERE DATE(created_at) = DATE('now', 'localtime')")->fetchColumn();
        $totalBytes     = (int) $this->db->query("SELECT COALESCE(SUM(filesize), 0) FROM download_history")->fetchColumn();

        $topPlatformRow = $this->db->query("
            SELECT platform, COUNT(*) as cnt 
            FROM download_history 
            GROUP BY platform 
            ORDER BY cnt DESC 
            LIMIT 1
        ")->fetch();
        $topPlatform = $topPlatformRow['platform'] ?? 'Umum';

        // 2. Data Grafik sesuai Rentang (Mingguan / Bulanan / Tahunan)
        $labels   = [];
        $counts   = [];
        $sizesMb  = [];

        if ($range === 'weekly') {
            // 7 hari terakhir
            for ($i = 6; $i >= 0; $i--) {
                $targetDate = date('Y-m-d', strtotime("-{$i} days"));
                $displayLabel = date('d M', strtotime($targetDate));

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as cnt, COALESCE(SUM(filesize), 0) as bytes 
                    FROM download_history 
                    WHERE DATE(created_at) = :dt
                ");
                $stmt->execute([':dt' => $targetDate]);
                $row = $stmt->fetch();

                $labels[]  = $displayLabel;
                $counts[]  = (int) ($row['cnt'] ?? 0);
                $sizesMb[] = round(((int) ($row['bytes'] ?? 0)) / (1024 * 1024), 2);
            }
        } elseif ($range === 'monthly') {
            // 30 hari terakhir (dikelompokkan per interval 3 hari agar grafik tetap bersih)
            for ($i = 29; $i >= 0; $i -= 3) {
                $endDate = date('Y-m-d', strtotime("-{$i} days"));
                $startDate = date('Y-m-d', strtotime("-" . ($i + 2) . " days"));
                $displayLabel = date('d M', strtotime($endDate));

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as cnt, COALESCE(SUM(filesize), 0) as bytes 
                    FROM download_history 
                    WHERE DATE(created_at) >= :start AND DATE(created_at) <= :end
                ");
                $stmt->execute([':start' => $startDate, ':end' => $endDate]);
                $row = $stmt->fetch();

                $labels[]  = $displayLabel;
                $counts[]  = (int) ($row['cnt'] ?? 0);
                $sizesMb[] = round(((int) ($row['bytes'] ?? 0)) / (1024 * 1024), 2);
            }
        } elseif ($range === 'yearly') {
            // 12 bulan terakhir
            for ($i = 11; $i >= 0; $i--) {
                $monthStr = date('Y-m', strtotime("-{$i} months"));
                $displayLabel = date('M Y', strtotime("-{$i} months"));

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as cnt, COALESCE(SUM(filesize), 0) as bytes 
                    FROM download_history 
                    WHERE strftime('%Y-%m', created_at) = :m
                ");
                $stmt->execute([':m' => $monthStr]);
                $row = $stmt->fetch();

                $labels[]  = $displayLabel;
                $counts[]  = (int) ($row['cnt'] ?? 0);
                $sizesMb[] = round(((int) ($row['bytes'] ?? 0)) / (1024 * 1024), 2);
            }
        }

        return [
            'chart' => [
                'range'    => $range,
                'labels'   => $labels,
                'counts'   => $counts,
                'sizes_mb' => $sizesMb,
            ],
            'summary' => [
                'total_downloads' => $totalDownloads,
                'today_downloads' => $todayDownloads,
                'total_bytes'     => $totalBytes,
                'total_bytes_fmt' => $this->formatBytes($totalBytes),
                'top_platform'    => $topPlatform,
            ]
        ];
    }

    /**
     * Hapus riwayat unduhan (berdasarkan rentang tanggal atau hapus semua)
     */
    public function deleteHistory(string $mode, ?string $startDate = null, ?string $endDate = null): int
    {
        if (!$this->db) return 0;

        try {
            if ($mode === 'all') {
                $deletedCount = (int) $this->db->query("SELECT COUNT(*) FROM download_history")->fetchColumn();
                $this->db->exec("DELETE FROM download_history");
                $this->db->exec("VACUUM");
                return $deletedCount;
            }

            if ($mode === 'range' && !empty($startDate) && !empty($endDate)) {
                $start = date('Y-m-d 00:00:00', strtotime($startDate));
                $end   = date('Y-m-d 23:59:59', strtotime($endDate));

                $stmtCount = $this->db->prepare("
                    SELECT COUNT(*) FROM download_history 
                    WHERE created_at >= :start AND created_at <= :end
                ");
                $stmtCount->execute([':start' => $start, ':end' => $end]);
                $deletedCount = (int) $stmtCount->fetchColumn();

                $stmtDel = $this->db->prepare("
                    DELETE FROM download_history 
                    WHERE created_at >= :start AND created_at <= :end
                ");
                $stmtDel->execute([':start' => $start, ':end' => $end]);
                $this->db->exec("VACUUM");

                return $deletedCount;
            }

            return 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Hapus riwayat unduhan yang lebih lama dari rentang hari tertentu (untuk cron job maintenance)
     * Otomatis menjalankan VACUUM untuk mengompres ukuran file SQLite.
     */
    public function cleanOldRecords(int $days = 60): int
    {
        if (!$this->db) return 0;

        try {
            $threshold = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM download_history WHERE created_at < :thresh");
            $stmtCount->execute([':thresh' => $threshold]);
            $count = (int) $stmtCount->fetchColumn();

            if ($count > 0) {
                $stmtDel = $this->db->prepare("DELETE FROM download_history WHERE created_at < :thresh");
                $stmtDel->execute([':thresh' => $threshold]);
                $this->db->exec("VACUUM");
            }

            return $count;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Dapatkan status fisik database SQLite untuk Monitor DB
     */
    public function getDbStats(): array
    {
        $fileSize = file_exists($this->dbPath) ? filesize($this->dbPath) : 0;
        $totalRows = 0;
        $firstDate = '-';
        $latestDate = '-';

        if ($this->db) {
            try {
                $totalRows = (int) $this->db->query("SELECT COUNT(*) FROM download_history")->fetchColumn();
                $firstRow = $this->db->query("SELECT created_at FROM download_history ORDER BY id ASC LIMIT 1")->fetch();
                $latestRow = $this->db->query("SELECT created_at FROM download_history ORDER BY id DESC LIMIT 1")->fetch();

                if ($firstRow && !empty($firstRow['created_at'])) {
                    $firstDate = date('d M Y, H:i', strtotime($firstRow['created_at']));
                }
                if ($latestRow && !empty($latestRow['created_at'])) {
                    $latestDate = date('d M Y, H:i', strtotime($latestRow['created_at']));
                }
            } catch (\Throwable $e) {
                // Abaikan
            }
        }

        return [
            'file_path'      => $this->dbPath,
            'file_size'      => $fileSize,
            'file_size_fmt'  => $this->formatBytes($fileSize),
            'total_rows'     => $totalRows,
            'first_record'   => $firstDate,
            'latest_record'  => $latestDate,
        ];
    }

    /**
     * Ambil riwayat unduhan terakhir (kompatibilitas)
     */
    public function getRecent(int $limit = 15): array
    {
        if (!$this->db) return [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM download_history ORDER BY id DESC LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Helper format ukuran bytes ke KB/MB/GB
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), $precision) . ' ' . ($units[$i] ?? 'B');
    }
}
