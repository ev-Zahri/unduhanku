<?php

/**
 * Database.php
 * 
 * Kelas ini bertanggung jawab membuat koneksi ke database menggunakan PDO.
 * 
 * PDO (PHP Data Objects) adalah cara modern dan aman untuk berinteraksi
 * dengan database di PHP. Keunggulannya:
 *  - Mendukung banyak jenis database (MySQL, PostgreSQL, SQLite, dll)
 *  - Mendukung Prepared Statements (mencegah SQL Injection)
 *  - Error handling yang lebih baik
 * 
 * Pola Singleton digunakan agar koneksi database hanya dibuat SATU KALI
 * selama aplikasi berjalan (hemat resource).
 */

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    // Menyimpan satu-satunya instance koneksi
    private static ?PDO $instance = null;

    /**
     * Private constructor mencegah pembuatan object dengan "new Database()"
     * dari luar kelas. Ini bagian dari pola Singleton.
     */
    private function __construct() {}

    /**
     * Metode utama untuk mendapatkan koneksi database.
     * Jika koneksi belum ada, buat baru. Jika sudah ada, kembalikan yang lama.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = $_ENV['DB_HOST']     ?? 'localhost';
            $port     = $_ENV['DB_PORT']     ?? '3306';
            $dbname   = $_ENV['DB_DATABASE'] ?? 'zuzu_db';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            // DSN = Data Source Name, format string koneksi untuk PDO
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $username, $password, [
                    // Lempar exception saat terjadi error (bukan hanya return false)
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    // Kembalikan hasil query sebagai array associative
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Matikan emulasi prepared statement (lebih aman)
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Di production, jangan tampilkan pesan error ke user!
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'Koneksi database gagal: ' . $e->getMessage()
                ]));
            }
        }

        return self::$instance;
    }
}
