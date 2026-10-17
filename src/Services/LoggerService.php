<?php

/**
 * LoggerService.php - Server-Side Error Logger
 * 
 * Mencatat semua error teknis ke file logs/error-YYYY-MM-DD.log
 * tanpa membocorkan detail internal ke sisi klien.
 */

namespace App\Services;

class LoggerService
{
    private static ?string $logDir = null;

    /**
     * Inisialisasi folder logs/
     */
    public static function getLogDir(): string
    {
        if (self::$logDir === null) {
            $rootDir = dirname(__DIR__, 2);
            self::$logDir = $rootDir . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0777, true);
            }
        }
        return self::$logDir;
    }

    /**
     * Catat error ke file log harian
     * 
     * @param string $message Pesan error teknis
     * @param array $context Data kontekstual (URL, format, input user)
     * @param ?\Throwable $exception Objek exception jika ada
     * @return string Kode referensi error unik untuk klien (misal: ERR-9F8A12)
     */
    public static function error(string $message, array $context = [], ?\Throwable $exception = null): string
    {
        $errorId   = 'ERR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $timestamp = date('Y-m-d H:i:s');
        $ip        = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $uri       = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $method    = $_SERVER['REQUEST_METHOD'] ?? 'CLI';

        $logFile = self::getLogDir() . DIRECTORY_SEPARATOR . 'error-' . date('Y-m-d') . '.log';

        $logEntry  = "================================================================================\n";
        $logEntry .= "[{$timestamp}] [ERROR] [{$errorId}] [{$ip}] [{$method} {$uri}]\n";
        $logEntry .= "Message: {$message}\n";

        if (!empty($context)) {
            // Sensor password atau kredensial jika ada di context
            $sanitizedContext = self::sanitize($context);
            $logEntry .= "Context: " . json_encode($sanitizedContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }

        if ($exception) {
            $logEntry .= "Exception: " . get_class($exception) . "\n";
            $logEntry .= "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
            $logEntry .= "Stack Trace:\n" . $exception->getTraceAsString() . "\n";
        }

        $logEntry .= "================================================================================\n\n";

        // Tulis log secara atomic / thread-safe dengan FILE_APPEND dan LOCK_EX
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        return $errorId;
    }

    /**
     * Catat info umum / audit
     */
    public static function info(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::getLogDir() . DIRECTORY_SEPARATOR . 'app-' . date('Y-m-d') . '.log';
        $logEntry = "[{$timestamp}] [INFO] {$message} " . (!empty($context) ? json_encode(self::sanitize($context)) : '') . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Sensor data sensitif sebelum ditulis ke log
     */
    private static function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'auth', 'cookie', 'secret', 'key'];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $data[$key] = self::sanitize($val);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $sensitive) {
                    if (stripos($key, $sensitive) !== false) {
                        $data[$key] = '********';
                        break;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Dapatkan daftar seluruh file log di server untuk Monitor DB
     */
    public static function listLogs(): array
    {
        $logDir = self::getLogDir();
        $files = glob($logDir . DIRECTORY_SEPARATOR . '*.log');
        $result = [];

        if ($files) {
            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

            foreach ($files as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $mtime = filemtime($file);

                $result[] = [
                    'filename'      => $filename,
                    'size'          => $size,
                    'size_fmt'      => self::formatBytes($size),
                    'modified_at'   => date('Y-m-d H:i:s', $mtime),
                    'modified_fmt'  => date('d M Y, H:i', $mtime),
                ];
            }
        }

        return $result;
    }

    /**
     * Helper format ukuran bytes ke KB/MB
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . ($units[$i] ?? 'B');
    }
}

