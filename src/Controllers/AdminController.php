<?php

/**
 * AdminController.php
 * 
 * Mengelola otentikasi admin, dashboard analitik, paginasi riwayat,
 * manajemen pembersihan database, dan download file log server.
 */

namespace App\Controllers;

use App\Services\HistoryService;
use App\Services\LoggerService;
use App\Services\AdvertisementService;

class AdminController
{
    private HistoryService $history;
    private AdvertisementService $ads;
    private string $adminUser;
    private string $adminPass;
    private string $adminPath;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->history   = new HistoryService();
        $this->ads       = new AdvertisementService();
        $this->adminUser = $_ENV['ADMIN_USERNAME'] ?? 'winteraespa';
        $this->adminPass = $_ENV['ADMIN_PASSWORD'] ?? 'winteraespa';
        $this->adminPath = $_ENV['ADMIN_PATH'] ?? '/secret-adm-console';
    }

    /**
     * Cek status autentikasi admin
     */
    private function isAuthenticated(): bool
    {
        return !empty($_SESSION['admin_auth']);
    }

    /**
     * Tampilkan Halaman Dashboard / Halaman Login
     */
    public function index(): void
    {
        $isLoggedIn = $this->isAuthenticated();
        $adminPath  = $this->adminPath;
        
        // Render view HTML
        require_once dirname(__DIR__) . '/Views/admin.php';
    }

    /**
     * POST /api/admin/login
     */
    public function login(): void
    {
        $input = $this->getJsonInput();
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if ($username === $this->adminUser && $password === $this->adminPass) {
            $_SESSION['admin_auth'] = true;
            $_SESSION['admin_logged_at'] = date('Y-m-d H:i:s');
            $this->respondJson(true, 'Login berhasil. Selamat datang di Console Admin.', [
                'redirect' => $this->adminPath
            ]);
            return;
        }

        $this->respondJson(false, 'Username atau password admin tidak valid.', null, 401);
    }

    /**
     * POST /api/admin/logout
     */
    public function logout(): void
    {
        $_SESSION['admin_auth'] = null;
        unset($_SESSION['admin_auth']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        if (isset($_GET['redirect'])) {
            header("Location: {$this->adminPath}");
            exit();
        }

        $this->respondJson(true, 'Sesi admin telah diakhiri.');
    }

    /**
     * GET /api/admin/analytics?range=weekly|monthly|yearly
     */
    public function analytics(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $range = strtolower(trim($_GET['range'] ?? 'weekly'));
        if (!in_array($range, ['weekly', 'monthly', 'yearly'])) {
            $range = 'weekly';
        }

        $data = $this->history->getAnalytics($range);
        $this->respondJson(true, 'Data analitik', $data);
    }

    /**
     * GET /api/admin/history?page=1
     * Paginasi riwayat unduhan dengan limit 10
     */
    public function history(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = $this->history->getPaginated($page, 10);
        $this->respondJson(true, 'Riwayat unduhan', $data);
    }

    /**
     * GET /api/admin/db-stats
     */
    public function dbStats(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak.', null, 401);
            return;
        }

        $stats = $this->history->getDbStats();
        $this->respondJson(true, 'Status database', $stats);
    }

    /**
     * POST /api/admin/history/delete
     */
    public function deleteHistory(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak.', null, 401);
            return;
        }

        $input = $this->getJsonInput();
        $mode  = trim($input['mode'] ?? '');
        $start = trim($input['start_date'] ?? '');
        $end   = trim($input['end_date'] ?? '');

        if ($mode !== 'all' && $mode !== 'range') {
            $this->respondJson(false, 'Mode penghapusan tidak valid.', null, 422);
            return;
        }

        if ($mode === 'range' && (empty($start) || empty($end))) {
            $this->respondJson(false, 'Tanggal mulai dan tanggal selesai wajib diisi.', null, 422);
            return;
        }

        $deletedCount = $this->history->deleteHistory($mode, $start, $end);

        $msg = $mode === 'all'
            ? "Seluruh riwayat unduhan ({$deletedCount} baris) berhasil dihapus dari database."
            : "{$deletedCount} baris riwayat unduhan pada rentang tanggal tersebut berhasil dihapus.";

        $this->respondJson(true, $msg, [
            'deleted_count' => $deletedCount,
            'db_stats'      => $this->history->getDbStats(),
        ]);
    }

    /**
     * GET /api/admin/logs
     */
    public function listLogs(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak.', null, 401);
            return;
        }

        $logs = LoggerService::listLogs();
        $this->respondJson(true, 'Daftar log server', $logs);
    }

    /**
     * GET /api/admin/logs/download?file=...
     */
    public function downloadLog(): void
    {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo "Akses ditolak.";
            return;
        }

        $filename = basename($_GET['file'] ?? '');
        if (empty($filename) || !str_ends_with($filename, '.log')) {
            http_response_code(400);
            echo "Nama file log tidak valid.";
            return;
        }

        $filePath = LoggerService::getLogDir() . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "File log tidak ditemukan di server.";
            return;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit();
    }

    // =========================================================
    // FITUR ADVERTISMENT (IKLAN)
    // =========================================================

    /**
     * GET /api/admin/ads?page=1&limit=10&search=...
     */
    public function listAds(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $search = trim($_GET['search'] ?? '');

        $result = $this->ads->getPaginated($page, $limit, $search);
        $this->respondJson(true, 'Data iklan berhasil dimuat', $result);
    }

    /**
     * POST /api/admin/ads/create
     */
    public function createAd(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $input = $this->getJsonInput();
        $name = trim($input['product_name'] ?? '');

        if (empty($name)) {
            $this->respondJson(false, 'Nama produk wajib diisi.', null, 400);
            return;
        }

        $id = $this->ads->create($input);
        if ($id > 0) {
            $this->respondJson(true, 'Iklan berhasil ditambahkan.', ['id' => $id]);
        } else {
            $this->respondJson(false, 'Gagal menyimpan data iklan.', null, 500);
        }
    }

    /**
     * POST /api/admin/ads/toggle
     */
    public function toggleAdLink(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $input = $this->getJsonInput();
        $id = (int)($input['id'] ?? 0);
        $targetType = trim($input['target_type'] ?? '');

        if ($id <= 0 || !in_array($targetType, ['product', 'extra'])) {
            $this->respondJson(false, 'Parameter tidak valid.', null, 400);
            return;
        }

        $res = $this->ads->toggleLink($id, $targetType);
        if ($res['success'] ?? false) {
            $this->respondJson(true, $res['message'], $res);
        } else {
            $this->respondJson(false, $res['message'] ?? 'Gagal mengubah status link iklan.', null, 400);
        }
    }

    /**
     * POST /api/admin/ads/delete
     */
    public function deleteAd(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $input = $this->getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            $this->respondJson(false, 'ID iklan tidak valid.', null, 400);
            return;
        }

        $ok = $this->ads->delete($id);
        if ($ok) {
            $this->respondJson(true, 'Iklan berhasil dihapus.');
        } else {
            $this->respondJson(false, 'Gagal menghapus iklan.', null, 500);
        }
    }

    /**
     * POST /api/admin/ads/bulk-preview
     * Menerima file upload CSV/Excel atau teks mentah, lalu membaca baris dan mengembalikan preview 5 baris serta daftar kolom
     */
    public function bulkPreview(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $content = '';
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $content = file_get_contents($_FILES['file']['tmp_name']);
        } else {
            $input = $this->getJsonInput();
            $content = $input['content'] ?? '';
        }

        if (empty($content)) {
            $this->respondJson(false, 'File CSV tidak ditemukan atau kosong.', null, 400);
            return;
        }

        // Hilangkan UTF-8 BOM jika ada
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Deteksi delimiter (koma atau titik koma)
        $firstLine = strtok($content, "\r\n");
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        // Parse CSV baris per baris
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        $allRows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $row = str_getcsv($line, $delimiter);
            if (!empty($row)) {
                $allRows[] = $row;
            }
        }

        if (count($allRows) < 2) {
            $this->respondJson(false, 'Format CSV tidak valid atau tidak memiliki baris data.', null, 400);
            return;
        }

        $headers = array_map('trim', $allRows[0]);
        $dataRows = array_slice($allRows, 1);

        // Simpan data sementara ke file cache di storage agar import massal cepat tanpa re-upload
        $token = bin2hex(random_bytes(16));
        $tempPath = dirname(__DIR__, 2) . '/storage/temp_bulk_' . $token . '.json';
        file_put_contents($tempPath, json_encode([
            'headers'  => $headers,
            'dataRows' => $dataRows
        ], JSON_UNESCAPED_UNICODE));

        // Buat mapping otomatis jika nama header cocok
        $suggestedMapping = [
            'product_name'          => null,
            'commission'            => null,
            'product_link'          => null,
            'extra_commission_link' => null
        ];

        foreach ($headers as $idx => $hName) {
            $hLower = strtolower($hName);
            if (str_contains($hLower, 'nama') && str_contains($hLower, 'produk')) {
                $suggestedMapping['product_name'] = $idx;
            } elseif (str_contains($hLower, 'ekstra') || str_contains($hLower, 'extra')) {
                $suggestedMapping['extra_commission_link'] = $idx;
            } elseif (str_contains($hLower, 'link') && str_contains($hLower, 'produk')) {
                $suggestedMapping['product_link'] = $idx;
            } elseif (str_contains($hLower, 'komisi') && !str_contains($hLower, 'ekstra') && !str_contains($hLower, 'hingga')) {
                $suggestedMapping['commission'] = $idx;
            }
        }

        $this->respondJson(true, 'File berhasil diparsing', [
            'token'             => $token,
            'headers'           => $headers,
            'total_rows'        => count($dataRows),
            'preview_rows'      => array_slice($dataRows, 0, 5),
            'suggested_mapping' => $suggestedMapping
        ]);
    }

    /**
     * POST /api/admin/ads/bulk-import
     * Menerima token file temp dan mapping indeks kolom dari admin
     */
    public function bulkImport(): void
    {
        if (!$this->isAuthenticated()) {
            $this->respondJson(false, 'Akses ditolak. Sesi admin diperlukan.', null, 401);
            return;
        }

        $input   = $this->getJsonInput();
        $token   = preg_replace('/[^a-zA-Z0-9]/', '', $input['token'] ?? '');
        $mapping = $input['mapping'] ?? [];

        if (empty($token)) {
            $this->respondJson(false, 'Token file bulk import tidak valid.', null, 400);
            return;
        }

        $tempPath = dirname(__DIR__, 2) . '/storage/temp_bulk_' . $token . '.json';
        if (!file_exists($tempPath)) {
            $this->respondJson(false, 'Sesi upload telah kadaluarsa. Silakan upload ulang file CSV.', null, 400);
            return;
        }

        $cachedData = json_decode(file_get_contents($tempPath), true);
        $dataRows = $cachedData['dataRows'] ?? [];

        if (empty($dataRows)) {
            @unlink($tempPath);
            $this->respondJson(false, 'Tidak ada baris data untuk diimpor.', null, 400);
            return;
        }

        try {
            $count = $this->ads->bulkImport($dataRows, $mapping);
            @unlink($tempPath);

            $this->respondJson(true, "Berhasil mengimpor {$count} produk iklan ke sistem.", [
                'imported_count' => $count
            ]);
        } catch (\Throwable $e) {
            $this->respondJson(false, 'Terjadi kesalahan saat menyimpan data impor: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/ad/serve (Public Endpoint)
     * Mengembalikan 1 iklan aktif dengan komisi tertinggi untuk ditayangkan ke klien
     */
    public function serveActiveAd(): void
    {
        $ad = $this->ads->getActiveAd();
        if ($ad) {
            $this->respondJson(true, 'Iklan aktif tersedia', $ad);
        } else {
            $this->respondJson(false, 'Tidak ada iklan yang sedang aktif saat ini', null, 404);
        }
    }

    /**
     * POST /api/ad/click (Public Endpoint)
     * Mencatat penambahan klik iklan
     */
    public function recordAdClick(): void
    {
        $input = $this->getJsonInput();
        $id = (int)($input['ad_id'] ?? $input['id'] ?? 0);

        if ($id > 0) {
            $this->ads->recordClick($id);
            $this->respondJson(true, 'Klik iklan berhasil dicatat.');
        } else {
            $this->respondJson(false, 'ID iklan tidak valid.', null, 400);
        }
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
     * Helper input JSON
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
}

