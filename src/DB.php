<?php
require_once __DIR__ . '/../config.php';

class DB {
    private $pdo;

    public function __construct() {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function init() {
        $this->pdo->exec("CREATE EXTENSION IF NOT EXISTS \"pgcrypto\";");
        $query = "
        CREATE TABLE IF NOT EXISTS strings (
            id VARCHAR(64) PRIMARY KEY,
            value TEXT UNIQUE NOT NULL,
            properties JSONB NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        );
        ";
        $this->pdo->exec($query);
    }

    public function insertString(string $id, string $value, array $props, string $createdAt): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO strings (id, value, properties, created_at)
            VALUES (:id, :value, :props, :created_at)
        ");
        $stmt->execute([
            ':id' => $id,
            ':value' => $value,
            ':props' => json_encode($props),
            ':created_at' => $createdAt,
        ]);
    }

    public function getByValue(string $value): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM strings WHERE value = :value LIMIT 1");
        $stmt->execute([':value' => $value]);
        return $stmt->fetch() ?: null;
    }

    public function deleteByValue(string $value): bool {
        $stmt = $this->pdo->prepare('DELETE FROM strings WHERE value = :value');
        $stmt->execute(['value' => $value]);
        return $stmt->rowCount() > 0;
    }

    public function getAll(array $filters = []): array {
        $query = "SELECT * FROM strings";
        $conditions = [];
        $params = [];

        if (isset($filters['is_palindrome'])) {
            $conditions[] = "CAST(properties->>'is_palindrome' AS BOOLEAN) = CAST(:is_palindrome AS BOOLEAN)";
            $params[':is_palindrome'] = $filters['is_palindrome'] ? 'true' : 'false';
        }

        if (isset($filters['min_length'])) {
            $conditions[] = "CAST(properties->>'length' AS INTEGER) >= :min_length";
            $params[':min_length'] = $filters['min_length'];
        }

        if (isset($filters['max_length'])) {
            $conditions[] = "CAST(properties->>'length' AS INTEGER) <= :max_length";
            $params[':max_length'] = $filters['max_length'];
        }

        if (isset($filters['word_count'])) {
            $conditions[] = "CAST(properties->>'word_count' AS INTEGER) = :word_count";
            $params[':word_count'] = $filters['word_count'];
        }

        if (isset($filters['contains_character'])) {
            // Use jsonb_exists function instead of ? operator to avoid PDO conflict
            $conditions[] = "jsonb_exists(properties->'character_frequency_map', :contains_char)";
            $params[':contains_char'] = $filters['contains_character'];
        }

        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}