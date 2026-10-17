<?php

/**
 * public/index.php - Entry point aplikasi Unduhanku
 */

// https://www.pornhub.com/view_video.php?viewkey=6533b602f0d41

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router;
use App\Controllers\MediaController;
use App\Controllers\AdminController;
use App\Services\LoggerService;

// -------------------------------------------------------------
// Global Error & Exception Handler (Zero Leakage ke Sisi Client)
// -------------------------------------------------------------
set_exception_handler(function (\Throwable $e) {
    $errId = LoggerService::error('Uncaught Exception', [], $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success'    => false,
        'message'    => "Terjadi kesalahan internal pada server. (Kode Ref: {$errId})",
        'error_code' => $errId
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    LoggerService::error("PHP Notice/Warning: {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
        'code' => $errno
    ]);
    return true; // Jangan tampilkan ke output browser
});

// Muat environment variables (.env) dengan fallback native jika paket composer belum terunduh
if (file_exists(__DIR__ . '/../.env')) {
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    } else {
        // Fallback native parser jika vlucas/phpdotenv belum terpasang
        $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim(trim($val), '"\'');
            $_ENV[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

// CORS Headers untuk API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$router          = new Router();
$mediaController = new MediaController();
$adminController = new AdminController();
$adminPath       = $_ENV['ADMIN_PATH'] ?? '/secret-adm-console';

// -------------------------------------------------------------
// Halaman Utama Web Dashboard
// -------------------------------------------------------------
$router->get('/', function () {
    $htmlPath = __DIR__ . '/index.html';
    if (file_exists($htmlPath)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($htmlPath);
    } else {
        header('Content-Type: application/json');
    }
});

$router->get('/robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    readfile(__DIR__ . '/robots.txt');
});

$router->get('/sitemap.xml', function () {
    header('Content-Type: application/xml; charset=utf-8');
    readfile(__DIR__ . '/sitemap.xml');
});

// -------------------------------------------------------------
// Hidden Admin Dashboard & Analytics
// -------------------------------------------------------------
$router->get($adminPath, function () use ($adminController) {
    $adminController->index();
});

$router->post('/api/admin/login', function () use ($adminController) {
    $adminController->login();
});

$router->post('/api/admin/logout', function () use ($adminController) {
    $adminController->logout();
});

$router->get('/api/admin/analytics', function () use ($adminController) {
    $adminController->analytics();
});

$router->get('/api/admin/history', function () use ($adminController) {
    $adminController->history();
});

$router->get('/api/admin/db-stats', function () use ($adminController) {
    $adminController->dbStats();
});

$router->post('/api/admin/history/delete', function () use ($adminController) {
    $adminController->deleteHistory();
});

$router->get('/api/admin/logs', function () use ($adminController) {
    $adminController->listLogs();
});

$router->get('/api/admin/logs/download', function () use ($adminController) {
    $adminController->downloadLog();
});

// Admin Advertisement Management
$router->get('/api/admin/ads', function () use ($adminController) {
    $adminController->listAds();
});

$router->post('/api/admin/ads/create', function () use ($adminController) {
    $adminController->createAd();
});

$router->post('/api/admin/ads/toggle', function () use ($adminController) {
    $adminController->toggleAdLink();
});

$router->post('/api/admin/ads/delete', function () use ($adminController) {
    $adminController->deleteAd();
});

$router->post('/api/admin/ads/bulk-preview', function () use ($adminController) {
    $adminController->bulkPreview();
});

$router->post('/api/admin/ads/bulk-import', function () use ($adminController) {
    $adminController->bulkImport();
});

// -------------------------------------------------------------
// Public Advertisement Client APIs
// -------------------------------------------------------------
$router->get('/api/ad/serve', function () use ($adminController) {
    $adminController->serveActiveAd();
});

$router->post('/api/ad/click', function () use ($adminController) {
    $adminController->recordAdClick();
});

// -------------------------------------------------------------
// Core Media Downloader APIs
// -------------------------------------------------------------
$router->post('/api/analyze', function () use ($mediaController) {
    $mediaController->analyze();
});

$router->post('/api/download', function () use ($mediaController) {
    $mediaController->download();
});

$router->get('/api/file', function () use ($mediaController) {
    $mediaController->serveFile();
});

$router->get('/api/proxy-download', function () use ($mediaController) {
    $mediaController->proxyDownload();
});

$router->get('/api/history', function () use ($mediaController) {
    $mediaController->history();
});

$router->get('/api/status', function () {
    header('Content-Type: application/json');
    $pythonVersion = shell_exec('python --version 2>&1') ?: 'Python tidak terdeteksi';
    $ytDlpVersion  = shell_exec('yt-dlp --version 2>&1') ?: 'yt-dlp tidak terdeteksi';

    echo json_encode([
        'status'         => 'active',
        'app_name'       => 'Unduhanku - Social Media Downloader',
        'php_version'    => PHP_VERSION,
        'python_version' => trim($pythonVersion),
        'engine_version' => trim($ytDlpVersion),
        'storage_writable' => is_writable(__DIR__ . '/../storage'),
    ], JSON_PRETTY_PRINT);
});

// Jalankan router
$router->dispatch();
