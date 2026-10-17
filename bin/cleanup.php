<?php

/**
 * cleanup.php
 * 
 * CLI Maintenance Script untuk Antrean Cron Job:
 * 1. Membersihkan file berkas unduhan fisik di storage/downloads/ yang usang (>30 menit)
 * 2. Membersihkan file sementara upload bulk di storage/temp_bulk_*.json (>60 menit)
 * 3. Membersihkan data riwayat unduhan lama di SQLite (>60 hari) & menjalankan VACUUM
 * 
 * Penggunaan di Cron Linux (crontab -e):
 *   0 * * * * /usr/bin/php /var/www/unduhanku/bin/cleanup.php >> /var/www/unduhanku/logs/cron.log 2>&1
 * 
 * Penggunaan CLI manual:
 *   php bin/cleanup.php
 *   php bin/cleanup.php --days=30 --max-age=20
 */

declare(strict_types=1);

// Pastikan hanya bisa dijalankan lewat CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Akses ditolak: Skrip ini hanya dapat dijalankan melalui antarmuka Command Line (CLI).\n";
    exit(1);
}

// Muat autoloader
$rootDir = dirname(__DIR__);
$autoloader = $rootDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Fallback autoloader sederhana jika vendor belum terinstall
    spl_autoload_register(function ($class) use ($rootDir) {
        $prefix = 'App\\';
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $rootDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

// Parse argumen CLI
$options = getopt('', ['days::', 'max-age::', 'dry-run']);
$retentionDays = isset($options['days']) ? (int) $options['days'] : 60;
$maxAgeMinutes = isset($options['max-age']) ? (int) $options['max-age'] : 30;
$isDryRun      = isset($options['dry-run']);

$timestamp = date('Y-m-d H:i:s');
echo "========================================================\n";
echo "[{$timestamp}] [CLEANUP] Memulai Pemeliharaan Sistem Unduhanku\n";
if ($isDryRun) {
    echo "[{$timestamp}] [MODE] DRY-RUN (simulasi tanpa penghapusan nyata)\n";
}
echo "--------------------------------------------------------\n";

$storageDir = $rootDir . DIRECTORY_SEPARATOR . 'storage';
$downloadsDir = $storageDir . DIRECTORY_SEPARATOR . 'downloads';

// --------------------------------------------------------------------
// 1. Pembersihan Berkas Fisik Unduhan (storage/downloads/)
// --------------------------------------------------------------------
$deletedFiles = 0;
$freedBytes   = 0;
$now = time();
$maxAgeSeconds = $maxAgeMinutes * 60;

if (is_dir($downloadsDir)) {
    $files = scandir($downloadsDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.gitkeep') {
            continue;
        }
        $filePath = $downloadsDir . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath)) {
            $mtime = filemtime($filePath);
            $ageMinutes = round(($now - $mtime) / 60, 1);
            if (($now - $mtime) > $maxAgeSeconds) {
                $size = (int) filesize($filePath);
                if (!$isDryRun) {
                    if (@unlink($filePath)) {
                        $deletedFiles++;
                        $freedBytes += $size;
                    }
                } else {
                    $deletedFiles++;
                    $freedBytes += $size;
                    echo "  [DRY-RUN] Berkas usang: {$file} ({$ageMinutes} menit lalu, " . formatBytes($size) . ")\n";
                }
            }
        }
    }
}

// --------------------------------------------------------------------
// 2. Pembersihan File Sementara (storage/temp_bulk_*.json)
// --------------------------------------------------------------------
$deletedTempFiles = 0;
$tempFiles = glob($storageDir . DIRECTORY_SEPARATOR . 'temp_bulk_*.json') ?: [];
foreach ($tempFiles as $tFile) {
    if (is_file($tFile) && ($now - filemtime($tFile) > 3600)) {
        if (!$isDryRun) {
            if (@unlink($tFile)) {
                $deletedTempFiles++;
            }
        } else {
            $deletedTempFiles++;
        }
    }
}

$freedFormatted = formatBytes($freedBytes);
echo "[{$timestamp}] [STORAGE] Berkas unduhan dibersihkan: {$deletedFiles} file ({$freedFormatted} ruang dikosongkan, batas usia: {$maxAgeMinutes} menit)\n";
if ($deletedTempFiles > 0) {
    echo "[{$timestamp}] [TEMP] File bulk sementara dibersihkan: {$deletedTempFiles} file\n";
}

// --------------------------------------------------------------------
// 3. Pembersihan Database Riwayat Unduhan (SQLite)
// --------------------------------------------------------------------
$historyService = new \App\Services\HistoryService();
$purgedRows = 0;

if (!$isDryRun) {
    $purgedRows = $historyService->cleanOldRecords($retentionDays);
} else {
    // Estimasi hitung
    try {
        $dbPath = $storageDir . DIRECTORY_SEPARATOR . 'history.sqlite';
        if (file_exists($dbPath)) {
            $pdo = new PDO("sqlite:{$dbPath}");
            $thresh = date('Y-m-d 00:00:00', strtotime("-{$retentionDays} days"));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM download_history WHERE created_at < :thresh");
            $stmt->execute([':thresh' => $thresh]);
            $purgedRows = (int) $stmt->fetchColumn();
        }
    } catch (\Throwable $e) {}
}

echo "[{$timestamp}] [DATABASE] Riwayat kedaluwarsa dihapus: {$purgedRows} baris (> {$retentionDays} hari)\n";

// Dapatkan statistik terkini database
$dbStats = $historyService->getDbStats();
echo "[{$timestamp}] [STATUS DB] Ukuran SQLite: {$dbStats['file_size_fmt']}, Total Data Tersisa: {$dbStats['total_rows']} baris\n";

echo "--------------------------------------------------------\n";
echo "[{$timestamp}] [CLEANUP] Selesai dengan sukses (Exit code: 0)\n";
echo "========================================================\n\n";

exit(0);

/**
 * Helper format ukuran byte
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $precision) . ' ' . ($units[$i] ?? 'B');
}
