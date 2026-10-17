<?php

/**
 * MediaController.php
 * 
 * Controller untuk mengelola endpoint API Unduhanku:
 *  - POST /api/analyze  -> ekstrak metadata & daftar resolusi
 *  - POST /api/download -> proses pengunduhan media
 *  - GET  /api/file     -> stream file ke browser user
 *  - GET  /api/history  -> daftar unduhan terakhir
 */

namespace App\Controllers;

use App\Services\DownloaderService;
use App\Services\HistoryService;

class MediaController
{
    private DownloaderService $downloader;
    private HistoryService $history;

    public function __construct()
    {
        $this->downloader = new DownloaderService();
        $this->history    = new HistoryService();
    }

    /**
     * POST /api/analyze
     * Menganalisis URL sosial media dan mengembalikan daftar format/kualitas
     */
    public function analyze(): void
    {
        try {
            $input = $this->getJsonInput();
            $url   = trim($input['url'] ?? '');

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->respondJson(false, 'Harap masukkan tautan (URL) yang valid.', null, 422);
                return;
            }

            $result = $this->downloader->getInfo($url);

            if (!($result['success'] ?? false)) {
                $this->respondJson(false, $result['error'] ?? 'Gagal menganalisis media.', [
                    'error_code' => $result['error_code'] ?? null
                ], 400);
                return;
            }

            $this->respondJson(true, 'Media berhasil dianalisis', $result['data']);
        } catch (\Throwable $e) {
            $errId = \App\Services\LoggerService::error('Exception in analyze()', ['input' => $input ?? []], $e);
            $this->respondJson(false, "Terjadi kesalahan internal pada server. (Kode Ref: {$errId})", [
                'error_code' => $errId
            ], 500);
        }
    }

    /**
     * POST /api/download
     * Memulai pengunduhan media dengan format yang dipilih
     */
    public function download(): void
    {
        try {
            $input     = $this->getJsonInput();
            $url       = trim($input['url'] ?? '');
            $formatId  = trim($input['format_id'] ?? 'best');
            $title     = trim($input['title'] ?? '');
            $platform  = trim($input['platform'] ?? '');
            $thumbnail = trim($input['thumbnail'] ?? '');

            if (empty($url)) {
                $this->respondJson(false, 'Parameter URL tidak boleh kosong.', null, 422);
                return;
            }

            $result = $this->downloader->download($url, $formatId);

            if (!($result['success'] ?? false)) {
                $this->respondJson(false, $result['error'] ?? 'Gagal mengunduh media.', [
                    'error_code' => $result['error_code'] ?? null
                ], 500);
                return;
            }

            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (str_contains($clientIp, ',')) {
                $clientIp = trim(explode(',', $clientIp)[0]);
            }

            // Catat ke riwayat
            $this->history->log([
                'title'      => $result['title'] ?? $title,
                'url'        => $url,
                'platform'   => $platform,
                'filename'   => $result['filename'] ?? '',
                'filesize'   => $result['filesize'] ?? 0,
                'thumbnail'  => $thumbnail,
                'ip_address' => $clientIp,
            ]);

            $filename = $result['filename'] ?? '';
            $fileUrl  = "api/file?name=" . urlencode($filename);

            $this->respondJson(true, 'Unduhan selesai dan siap disimpan', [
                'filename'     => $filename,
                'filesize'     => $result['filesize'] ?? 0,
                'filesize_fmt' => $this->formatBytes($result['filesize'] ?? 0),
                'download_url' => $fileUrl,
                'title'        => $result['title'] ?? $title
            ]);
        } catch (\Throwable $e) {
            $errId = \App\Services\LoggerService::error('Exception in download()', ['input' => $input ?? []], $e);
            $this->respondJson(false, "Terjadi kendala saat memproses unduhan. (Kode Ref: {$errId})", [
                'error_code' => $errId
            ], 500);
        }
    }

    /**
     * GET /api/file?name=...
     * Mengalirkan file fisik dari storage ke browser pengguna
     */
    public function serveFile(): void
    {
        $filename = basename($_GET['name'] ?? '');

        if (empty($filename)) {
            http_response_code(400);
            echo "Nama file tidak valid";
            return;
        }

        $filePath = $this->downloader->getStorageDir() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "File tidak ditemukan di server";
            return;
        }

        // Tentukan MIME type
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4'  => 'video/mp4',
            'mkv'  => 'video/x-matroska',
            'webm' => 'video/webm',
            'mp3'  => 'audio/mpeg',
            'm4a'  => 'audio/mp4',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp'
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        // Set header download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Bersihkan output buffer sebelum kirim file
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Auto-delete file fisik dari storage segera setelah pengiriman data selesai
        // Memastikan file temporer langsung dihapus dari disk server pasca-unduh client
        register_shutdown_function(function () use ($filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        });

        readfile($filePath);
        exit();
    }

    /**
     * GET /api/proxy-download?url=...&name=...
     * Mengalirkan media eksternal (video progresif / foto CDN) langsung ke browser sebagai attachment
     * - Memangkas beban storage (0 byte disk write)
     * - Bypass limit CORS browser dan otomatis mencatat analitik riwayat
     */
    public function proxyDownload(): void
    {
        @set_time_limit(0);

        $url         = trim($_GET['url'] ?? '');
        $name        = trim($_GET['name'] ?? 'unduhan.mp4');
        $title       = trim($_GET['title'] ?? '');
        $platform    = trim($_GET['platform'] ?? '');
        $originalUrl = trim($_GET['original_url'] ?? '');
        $filesize    = (int) ($_GET['filesize'] ?? 0);

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo "URL tidak valid";
            return;
        }

        // Bersihkan nama file dari karakter berbahaya
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
        if (empty($safeName)) {
            $safeName = 'unduhan.mp4';
        }

        // Tentukan ekstensi & MIME type
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'mp4';
            $safeName .= '.mp4';
        }

        // Catat ke riwayat unduhan (SQLite) jika parameter identitas tersedia
        if (!empty($title) || !empty($originalUrl)) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (str_contains($clientIp, ',')) {
                $clientIp = trim(explode(',', $clientIp)[0]);
            }

            $this->history->log([
                'title'      => !empty($title) ? $title : $safeName,
                'url'        => !empty($originalUrl) ? $originalUrl : $url,
                'platform'   => !empty($platform) ? $platform : 'Web',
                'filename'   => $safeName,
                'filesize'   => $filesize,
                'thumbnail'  => '',
                'ip_address' => $clientIp,
            ]);
        }

        $mimeTypes = [
            'mp4'  => 'video/mp4',
            'mkv'  => 'video/x-matroska',
            'webm' => 'video/webm',
            'mp3'  => 'audio/mpeg',
            'm4a'  => 'audio/mp4',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp'
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        // Bersihkan output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . addslashes($safeName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Tentukan Referer berdasarkan domain sumber CDN
        $referer = 'https://www.google.com/';
        if (str_contains($url, 'facebook.com') || (str_contains($url, 'fbcdn.net') && !str_contains($url, 'instagram'))) {
            $referer = 'https://www.facebook.com/';
        } elseif (str_contains($url, 'cdninstagram.com') || str_contains($url, 'instagram.com') || str_contains($url, 'fbcdn.net')) {
            $referer = 'https://www.instagram.com/';
        } elseif (str_contains($url, 'twimg.com') || str_contains($url, 'twitter.com') || str_contains($url, 'x.com')) {
            $referer = 'https://x.com/';
        } elseif (str_contains($url, 'tiktokcdn.com') || str_contains($url, 'tiktok.com')) {
            $referer = 'https://www.tiktok.com/';
        }

        // Gunakan cURL streaming jika tersedia untuk efisiensi memori & bypass User-Agent sosmed
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_REFERER, $referer);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\nReferer: {$referer}\r\n",
                    'timeout' => 300
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            $context = stream_context_create($opts);
            $fp = fopen($url, 'rb', false, $context);
            if ($fp) {
                fpassthru($fp);
                fclose($fp);
            } else {
                http_response_code(502);
                echo "Gagal mengalirkan media dari sumber eksternal.";
            }
        }
        exit();
    }

    /**
     * GET /api/history
     * Mengembalikan daftar 15 unduhan terakhir
     */
    public function history(): void
    {
        $list = $this->history->getRecent();
        
        // Format ukuran file untuk tampilan ramah
        foreach ($list as &$item) {
            $item['filesize_fmt'] = $this->formatBytes((int) ($item['filesize'] ?? 0));
            $item['download_url'] = !empty($item['filename']) ? "api/file?name=" . urlencode($item['filename']) : null;
        }

        $this->respondJson(true, 'Riwayat unduhan', $list);
    }

    /**
     * Helper response JSON
     */
    private function respondJson(bool $success, string $message, mixed $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Helper baca JSON input
     */
    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
        if (empty($data) && !empty($_POST)) {
            $data = $_POST;
        }
        return $data;
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
