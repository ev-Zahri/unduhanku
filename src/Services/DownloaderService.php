<?php

/**
 * DownloaderService.php
 * 
 * Service yang bertanggung jawab menghubungkan aplikasi PHP dengan engine
 * Python (extractor.py & yt-dlp) via process execution yang aman.
 */

namespace App\Services;

class DownloaderService
{
    private string $enginePath;
    private string $pythonBinary;
    private string $storageDir;

    public function __construct()
    {
        // Lokasi root direktori proyek
        $rootDir = dirname(__DIR__, 2);
        
        $this->enginePath   = $rootDir . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'extractor.py';
        $this->storageDir   = $rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'downloads';

        // Deteksi Python binary: Cek .env, lalu path umum Windows, lalu fallback ke 'python'
        $configuredPython = $_ENV['PYTHON_BIN'] ?? getenv('PYTHON_BIN') ?: '';
        if (!empty($configuredPython) && file_exists($configuredPython)) {
            $this->pythonBinary = "\"{$configuredPython}\"";
        } elseif (file_exists('C:\\Users\\ramad\\AppData\\Local\\Programs\\Python\\Python310\\python.exe')) {
            $this->pythonBinary = '"C:\\Users\\ramad\\AppData\\Local\\Programs\\Python\\Python310\\python.exe"';
        } else {
            $this->pythonBinary = 'python';
        }

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    /**
     * Dapatkan informasi media (judul, thumbnail, daftar format, durasi)
     */
    public function getInfo(string $url): array
    {
        $escapedUrl = escapeshellarg(trim($url));
        $command = "{$this->pythonBinary} \"{$this->enginePath}\" info {$escapedUrl}";

        $output = $this->executeCommand($command);

        $json = $this->extractJson($output);
        if (!$json || !($json['success'] ?? false)) {
            $rawError = $json['error'] ?? substr($output, 0, 1000);
            $errorId = LoggerService::error("Gagal getInfo media", [
                'url'     => $url,
                'command' => $command,
                'output'  => $output
            ]);

            return [
                'success'    => false,
                'error_code' => $errorId,
                'error'      => "Gagal menganalisis link media. Pastikan tautan bersifat publik dan masih aktif. (Kode Ref: {$errorId})"
            ];
        }

        return $json;
    }

    /**
     * Eksekusi proses unduh media ke folder storage/downloads
     */
    public function download(string $url, string $formatId = 'best'): array
    {
        // Pembersihan berkala otomatis: bersihkan file unduhan usang > 30 menit
        $this->cleanOldDownloads(30);

        $escapedUrl    = escapeshellarg(trim($url));
        $escapedFormat = escapeshellarg($formatId);
        $escapedDir    = escapeshellarg($this->storageDir);

        $command = "{$this->pythonBinary} \"{$this->enginePath}\" download {$escapedUrl} {$escapedFormat} {$escapedDir}";

        $output = $this->executeCommand($command);

        $json = $this->extractJson($output);
        if (!$json || !($json['success'] ?? false)) {
            $rawError = $json['error'] ?? substr($output, 0, 1000);
            $errorId = LoggerService::error("Gagal download media", [
                'url'       => $url,
                'format_id' => $formatId,
                'command'   => $command,
                'output'    => $output
            ]);

            return [
                'success'    => false,
                'error_code' => $errorId,
                'error'      => "Gagal mengunduh media dengan format yang dipilih. Silakan coba kualitas lain. (Kode Ref: {$errorId})"
            ];
        }

        return $json;
    }

    /**
     * Jalankan perintah CLI dengan timeout dan penangkapan stdout/stderr
     */
    private function executeCommand(string $command): string
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        // Pastikan variabel lingkungan Windows seperti SystemRoot dan PATH tetap terbawa
        $rawEnv = array_merge($_SERVER, [
            'SystemRoot'       => getenv('SystemRoot') ?: 'C:\\Windows',
            'SYSTEMROOT'       => getenv('SystemRoot') ?: 'C:\\Windows',
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONWARNINGS'   => 'ignore'
        ]);

        $env = [];
        foreach ($rawEnv as $k => $v) {
            if (is_scalar($v)) {
                $env[$k] = (string) $v;
            }
        }

        $process = proc_open($command, $descriptors, $pipes, null, $env);

        if (!is_resource($process)) {
            return '';
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        // Gabungkan stdout, atau gunakan stderr jika stdout kosong
        return trim($stdout) !== '' ? $stdout : $stderr;
    }

    /**
     * Cari baris JSON valid dalam string output (kebal terhadap output progress bar atau peringatan)
     */
    private function extractJson(string $raw): ?array
    {
        // 1. Ekstrak berbasis penanda unik __JSON_START__ dan __JSON_END__
        if (preg_match('/__JSON_START__(.*?)__JSON_END__/s', $raw, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 2. Ekstrak regex mencari object JSON {"success":...}
        if (preg_match('/\{"success":\s*(true|false).*\}/s', $raw, $matches)) {
            $decoded = json_decode(trim($matches[0]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 3. Fallback per baris
        $lines = explode("\n", str_replace("\r", "\n", $raw));
        foreach (array_reverse($lines) as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Bersihkan file unduhan fisik usang yang berumur lebih dari $maxAgeMinutes
     * Berguna untuk menjaga storage tetap bersih dari file unduhan yang ditinggalkan/dibatalkan client.
     */
    public function cleanOldDownloads(int $maxAgeMinutes = 30): array
    {
        $deleted = 0;
        $freedBytes = 0;

        if (is_dir($this->storageDir)) {
            $files = scandir($this->storageDir);
            $now = time();
            $maxAgeSeconds = $maxAgeMinutes * 60;

            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                    continue;
                }
                $path = $this->storageDir . DIRECTORY_SEPARATOR . $file;
                if (is_file($path)) {
                    $mtime = filemtime($path);
                    if ($now - $mtime > $maxAgeSeconds) {
                        $size = (int) @filesize($path);
                        if (@unlink($path)) {
                            $deleted++;
                            $freedBytes += $size;
                        }
                    }
                }
            }
        }

        // Bersihkan juga file sementara bulk upload seperti temp_bulk_*.json di storage/
        $parentStorage = dirname($this->storageDir);
        if (is_dir($parentStorage)) {
            $tempFiles = glob($parentStorage . DIRECTORY_SEPARATOR . 'temp_bulk_*.json');
            if ($tempFiles) {
                $now = time();
                foreach ($tempFiles as $tFile) {
                    if (is_file($tFile) && ($now - filemtime($tFile) > 3600)) {
                        @unlink($tFile);
                    }
                }
            }
        }

        return [
            'deleted_files' => $deleted,
            'freed_bytes'   => $freedBytes
        ];
    }

    public function getStorageDir(): string
    {
        return $this->storageDir;
    }
}
