<?php

/**
 * AdvertisementService.php
 * 
 * Mengelola data iklan afiliasi di SQLite (storage/history.sqlite),
 * mencakup manajemen manual, import massal (CSV/Excel), toggle status link aktif,
 * pagination limit 10, pencatatan klik, dan penayangan iklan berbasis komisi tertinggi.
 */

namespace App\Services;

use PDO;
use PDOException;

class AdvertisementService
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

            $this->initTable();
        } catch (PDOException $e) {
            $this->db = null;
        }
    }

    /**
     * Inisialisasi tabel advertisements
     */
    private function initTable(): void
    {
        if (!$this->db) return;

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS advertisements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_name TEXT NOT NULL,
                commission INTEGER DEFAULT 0,
                commission_raw TEXT,
                product_link TEXT,
                extra_commission_link TEXT,
                active_link_type TEXT DEFAULT 'none',
                click_count INTEGER DEFAULT 0,
                potential_issue TEXT DEFAULT 'Belum Aktif',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Index untuk optimasi penayangan iklan tercepat
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_ads_active ON advertisements(active_link_type, commission DESC)");
    }

    /**
     * Parse string komisi menjadi integer numerik untuk sorting
     * Contoh: "Rp13.000" -> 13000, "Rp 1.500" -> 1500, "25000" -> 25000
     */
    public function parseCommission(string $raw): int
    {
        $clean = preg_replace('/[^\d]/', '', $raw);
        return !empty($clean) ? (int)$clean : 0;
    }

    /**
     * Format integer komisi ke format tampilan Rupiah
     */
    public function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Potong string URL maksimal 20 huruf untuk tampilan tabel rapi
     */
    public function shortenLink(?string $url, int $max = 20): string
    {
        $url = trim($url ?? '');
        if (empty($url)) return '-';
        if (mb_strlen($url) <= $max) return $url;
        return mb_substr($url, 0, $max) . '...';
    }

    /**
     * Evaluasi potensi issue iklan
     */
    public function evaluatePotentialIssue(?string $prodLink, ?string $extraLink, string $activeType): string
    {
        $prodLink  = trim($prodLink ?? '');
        $extraLink = trim($extraLink ?? '');

        if (empty($prodLink) && empty($extraLink)) {
            return 'Link Kosong';
        }

        if ($activeType === 'none' || empty($activeType)) {
            return 'Belum Aktif';
        }

        if ($activeType === 'product') {
            if (empty($prodLink) || !filter_var($prodLink, FILTER_VALIDATE_URL)) {
                return 'Format Tidak Valid';
            }
            return 'Aman';
        }

        if ($activeType === 'extra') {
            if (empty($extraLink) || !filter_var($extraLink, FILTER_VALIDATE_URL)) {
                return 'Format Tidak Valid';
            }
            return 'Aman';
        }

        return 'Aman';
    }

    /**
     * Ambil data iklan terpaginasi (Limit 10)
     */
    public function getPaginated(int $page = 1, int $limit = 10, string $search = ''): array
    {
        if (!$this->db) {
            return [
                'items' => [],
                'pagination' => ['total_items' => 0, 'page' => 1, 'limit' => 10, 'total_pages' => 0],
                'stats' => ['total_ads' => 0, 'active_ads' => 0, 'total_clicks' => 0]
            ];
        }

        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $whereClause = "1=1";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (product_name LIKE :search OR commission_raw LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        // Hitung total items
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM advertisements WHERE {$whereClause}");
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();
        $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $limit) : 1;

        // Ambil data iklan teratas (diurutkan berdasarkan komisi tertinggi, lalu ID terbaru)
        $stmt = $this->db->prepare("
            SELECT * FROM advertisements 
            WHERE {$whereClause} 
            ORDER BY commission DESC, id DESC 
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Hitung statistik keseluruhan
        $statsStmt = $this->db->query("
            SELECT 
                COUNT(*) as total_ads,
                SUM(CASE WHEN active_link_type IN ('product', 'extra') THEN 1 ELSE 0 END) as active_ads,
                SUM(click_count) as total_clicks
            FROM advertisements
        ");
        $stats = $statsStmt->fetch() ?: ['total_ads' => 0, 'active_ads' => 0, 'total_clicks' => 0];

        $items = [];
        foreach ($rows as $row) {
            $commInt = (int)($row['commission'] ?? 0);
            $activeType = $row['active_link_type'] ?? 'none';
            $pLink = $row['product_link'] ?? '';
            $eLink = $row['extra_commission_link'] ?? '';

            $issue = $this->evaluatePotentialIssue($pLink, $eLink, $activeType);

            $items[] = [
                'id'                         => (int)$row['id'],
                'product_name'               => $row['product_name'],
                'commission'                 => $commInt,
                'commission_fmt'             => $this->formatRupiah($commInt),
                'commission_raw'             => $row['commission_raw'] ?: $this->formatRupiah($commInt),
                'product_link'               => $pLink,
                'product_link_short'         => $this->shortenLink($pLink, 20),
                'extra_commission_link'       => $eLink,
                'extra_commission_link_short' => $this->shortenLink($eLink, 20),
                'active_link_type'           => $activeType,
                'is_product_active'          => ($activeType === 'product'),
                'is_extra_active'            => ($activeType === 'extra'),
                'click_count'                => (int)($row['click_count'] ?? 0),
                'potential_issue'            => $issue,
                'created_at'                 => $row['created_at']
            ];
        }

        return [
            'items' => $items,
            'pagination' => [
                'total_items' => $totalItems,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $totalPages,
                'has_prev'    => $page > 1,
                'has_next'    => $page < $totalPages
            ],
            'stats' => [
                'total_ads'    => (int)($stats['total_ads'] ?? 0),
                'active_ads'   => (int)($stats['active_ads'] ?? 0),
                'total_clicks' => (int)($stats['total_clicks'] ?? 0)
            ]
        ];
    }

    /**
     * Tambah iklan manual
     */
    public function create(array $data): int
    {
        if (!$this->db) return 0;

        $name       = trim($data['product_name'] ?? '');
        $rawComm    = trim($data['commission'] ?? '0');
        $commInt    = $this->parseCommission($rawComm);
        $prodLink   = trim($data['product_link'] ?? '');
        $extraLink  = trim($data['extra_commission_link'] ?? '');
        $activeType = trim($data['active_link_type'] ?? 'none');

        if (!in_array($activeType, ['product', 'extra', 'none'])) {
            $activeType = 'none';
        }

        $issue = $this->evaluatePotentialIssue($prodLink, $extraLink, $activeType);

        $stmt = $this->db->prepare("
            INSERT INTO advertisements 
            (product_name, commission, commission_raw, product_link, extra_commission_link, active_link_type, click_count, potential_issue, created_at, updated_at)
            VALUES (:name, :comm, :raw_comm, :p_link, :e_link, :active, 0, :issue, datetime('now', 'localtime'), datetime('now', 'localtime'))
        ");

        $stmt->execute([
            ':name'     => $name,
            ':comm'     => $commInt,
            ':raw_comm' => !empty($rawComm) ? $rawComm : $this->formatRupiah($commInt),
            ':p_link'   => $prodLink,
            ':e_link'   => $extraLink,
            ':active'   => $activeType,
            ':issue'    => $issue
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Toggle status aktif link pada suatu iklan.
     * Aturan: dalam 1 iklan hanya 1 link yang bisa tayang (product atau extra).
     * Jika link yang diklik sudah aktif, maka nonaktifkan ('none').
     */
    public function toggleLink(int $id, string $targetType): array
    {
        if (!$this->db || !in_array($targetType, ['product', 'extra'])) {
            return ['success' => false, 'message' => 'Parameter tidak valid'];
        }

        $stmt = $this->db->prepare("SELECT * FROM advertisements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $ad = $stmt->fetch();

        if (!$ad) {
            return ['success' => false, 'message' => 'Iklan tidak ditemukan'];
        }

        $currentActive = $ad['active_link_type'] ?? 'none';
        $newActive = ($currentActive === $targetType) ? 'none' : $targetType;

        $issue = $this->evaluatePotentialIssue($ad['product_link'], $ad['extra_commission_link'], $newActive);

        $upStmt = $this->db->prepare("
            UPDATE advertisements 
            SET active_link_type = :active, potential_issue = :issue, updated_at = datetime('now', 'localtime')
            WHERE id = :id
        ");
        $upStmt->execute([
            ':active' => $newActive,
            ':issue'  => $issue,
            ':id'     => $id
        ]);

        return [
            'success'          => true,
            'id'               => $id,
            'active_link_type' => $newActive,
            'potential_issue'  => $issue,
            'message'          => $newActive === 'none' 
                                  ? 'Iklan berhasil dinonaktifkan' 
                                  : 'Link iklan berhasil diaktifkan untuk tayang'
        ];
    }

    /**
     * Hapus iklan
     */
    public function delete(int $id): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM advertisements WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Import massal iklan dari array baris dengan mapping kolom dari admin
     */
    public function bulkImport(array $rows, array $mapping): int
    {
        if (!$this->db || empty($rows)) return 0;

        $idxName  = $mapping['product_name'] ?? null;
        $idxComm  = $mapping['commission'] ?? null;
        $idxProd  = $mapping['product_link'] ?? null;
        $idxExtra = $mapping['extra_commission_link'] ?? null;

        $inserted = 0;
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO advertisements 
                (product_name, commission, commission_raw, product_link, extra_commission_link, active_link_type, click_count, potential_issue, created_at, updated_at)
                VALUES (:name, :comm, :raw_comm, :p_link, :e_link, :active, 0, :issue, datetime('now', 'localtime'), datetime('now', 'localtime'))
            ");

            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                $name = $idxName !== null && isset($row[$idxName]) ? trim((string)$row[$idxName]) : '';
                if (empty($name)) continue;

                $rawComm   = $idxComm !== null && isset($row[$idxComm]) ? trim((string)$row[$idxComm]) : '0';
                $commInt   = $this->parseCommission($rawComm);
                $pLink     = $idxProd !== null && isset($row[$idxProd]) ? trim((string)$row[$idxProd]) : '';
                $eLink     = $idxExtra !== null && isset($row[$idxExtra]) ? trim((string)$row[$idxExtra]) : '';

                // Default awal: jika Link Komisi Ekstra ada, aktifkan 'extra', jika tidak ada aktifkan 'product'
                $activeType = !empty($eLink) ? 'extra' : (!empty($pLink) ? 'product' : 'none');
                $issue = $this->evaluatePotentialIssue($pLink, $eLink, $activeType);

                $stmt->execute([
                    ':name'     => $name,
                    ':comm'     => $commInt,
                    ':raw_comm' => !empty($rawComm) ? $rawComm : $this->formatRupiah($commInt),
                    ':p_link'   => $pLink,
                    ':e_link'   => $eLink,
                    ':active'   => $activeType,
                    ':issue'    => $issue
                ]);

                $inserted++;
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return $inserted;
    }

    /**
     * Ambil 1 iklan aktif saat ini untuk ditayangkan ke klien.
     * Sesuai Opsi A: Prioritas Komisi Tertinggi (Highest Commission First).
     */
    public function getActiveAd(): ?array
    {
        if (!$this->db) return null;

        $stmt = $this->db->query("
            SELECT * FROM advertisements 
            WHERE active_link_type IN ('product', 'extra')
            ORDER BY commission DESC, id DESC 
            LIMIT 1
        ");
        $ad = $stmt->fetch();

        if (!$ad) return null;

        $activeType = $ad['active_link_type'] ?? 'product';
        $targetUrl  = ($activeType === 'extra') ? $ad['extra_commission_link'] : $ad['product_link'];

        if (empty($targetUrl)) {
            $targetUrl = !empty($ad['product_link']) ? $ad['product_link'] : $ad['extra_commission_link'];
        }

        if (empty($targetUrl)) return null;

        return [
            'id'           => (int)$ad['id'],
            'product_name' => $ad['product_name'],
            'url'          => $targetUrl,
            'type'         => $activeType,
            'commission'   => (int)$ad['commission']
        ];
    }

    /**
     * Catat penambahan jumlah klik iklan
     */
    public function recordClick(int $id): bool
    {
        if (!$this->db || $id <= 0) return false;

        $stmt = $this->db->prepare("
            UPDATE advertisements 
            SET click_count = click_count + 1, updated_at = datetime('now', 'localtime')
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }
}
