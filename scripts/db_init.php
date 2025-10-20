<?php
require_once __DIR__ . '/db.php';

$pdo = Database::getConnection();

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS analyzed_strings (
    id SERIAL PRIMARY KEY,
    input_text TEXT NOT NULL,
    length INTEGER NOT NULL,
    is_palindrome BOOLEAN NOT NULL,
    unique_characters INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL;

$pdo->exec($sql);

echo "✅ PostgreSQL database initialized successfully.\n";

