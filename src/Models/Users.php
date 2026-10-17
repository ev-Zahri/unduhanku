<?php


namespace App\Models;

use App\Database\Database;
use PDO;

class User
{
    private PDO $db;

    public ?int $id = null;
    public string $email = '';
    public string $username = '';
    public string $hash_password = '';
    public string $created_at = '';
    public string $update_at = '';

    public function __construct()
    {
        $this->db = Database::getConnection();
        echo "Database User has connected";
    }

    public function create(array $data): array
    {
        $sql = "INSERT INTO users (email, username, hash_password) VALUES (:email, :username, :hash_password)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $data['email'],
            ':username' => $data['username'],
            ':hash_password' => $data['hash_password'],
        ]);

        echo "Data User has created";
        return $this->findById((int) $this->db->lastInsertId());
    }

    public function findAll(int $page = 1, int $limit = 10, string $search = ''): array 
    {
        $offset = ($page - 1) * limit;
        if ($search) {
            $sql = "SELECT * FROM users WHERE username LIKE :search ORDER BY created_at DESC LIMIT :limit OFFFSET :ooffset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
        } else {
            $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindValue(':limit', $limit, PDO:PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO:PARAM_INT);

        $users = $stmt->fetchAll();
        $total = $this->count($search);

        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) ceil($total/$limit),
        ];
    }
    
}