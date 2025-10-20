<?php
require_once __DIR__ . '/DB.php';

class StringAnalyzer {
    private $db;

    public function __construct(DB $db) {
        $this->db = $db;
        $this->db->init();
    }

    public function analyze(string $value): array {
        $length = mb_strlen($value, 'UTF-8');
        $lower = mb_strtolower($value, 'UTF-8');
        $reversed = $this->mb_strrev($lower);
        $isPalindrome = ($lower === $reversed);

        $chars = $this->splitChars($value);
        $freq = [];
        foreach ($chars as $ch) {
            $freq[$ch] = ($freq[$ch] ?? 0) + 1;
        }

        $unique = count($freq);
        $wordCount = $this->wordCount($value);
        $sha = hash('sha256', $value);

        return [
            'length' => $length,
            'is_palindrome' => $isPalindrome,
            'unique_characters' => $unique,
            'word_count' => $wordCount,
            'sha256_hash' => $sha,
            'character_frequency_map' => $freq,
        ];
    }

    public function create(string $value): array {
        $existing = $this->db->getByValue($value);
        if ($existing) {
            throw new RuntimeException('String already exists.');
        }

        $props = $this->analyze($value);
        $now = gmdate('c');
        $this->db->insertString($props['sha256_hash'], $value, $props, $now);

        return [
            'id' => $props['sha256_hash'],
            'value' => $value,
            'properties' => $props,
            'created_at' => $now,
        ];
    }

    public function getByValue(string $value) {
        $row = $this->db->getByValue($value);
        if (!$row) return null;

        return [
            'id' => $row['id'],
            'value' => $row['value'],
            'properties' => json_decode($row['properties'], true),
            'created_at' => $row['created_at'],
        ];
    }

    public function deleteByValue(string $value): bool {
        $row = $this->db->getByValue($value);
        if (!$row) return false; // string does not exist

        $this->db->deleteByValue($value); // actually delete from DB
        return true;
    }

    public function getAll(array $query): array {
        $filters = [];

        if (isset($query['is_palindrome'])) {
            $filters['is_palindrome'] = filter_var(
                $query['is_palindrome'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            if ($filters['is_palindrome'] === null) {
                throw new InvalidArgumentException('Invalid is_palindrome value');
            }
        }

        if (isset($query['min_length'])) {
            if (!is_numeric($query['min_length'])) throw new InvalidArgumentException('Invalid min_length');
            $filters['min_length'] = (int)$query['min_length'];
        }

        if (isset($query['max_length'])) {
            if (!is_numeric($query['max_length'])) throw new InvalidArgumentException('Invalid max_length');
            $filters['max_length'] = (int)$query['max_length'];
        }

        if (isset($query['word_count'])) {
            if (!is_numeric($query['word_count'])) throw new InvalidArgumentException('Invalid word_count');
            $filters['word_count'] = (int)$query['word_count'];
        }

        if (isset($query['contains_character'])) {
            $char = $query['contains_character'];
            if (strlen($char) !== 1) throw new InvalidArgumentException('contains_character must be a single character');
            $filters['contains_character'] = $char;
        }

        $rows = $this->db->getAll($filters);

        // decode properties for each row
        foreach ($rows as &$row) {
            if (isset($row['properties']) && is_string($row['properties'])) {
                $row['properties'] = json_decode($row['properties'], true);
            }
        }

        return $rows;
    }

    public function filterByNaturalLanguage(string $query): array {
        $filters = $this->parseNaturalLanguageQuery($query);

        if (!$filters) {
            throw new InvalidArgumentException('Unable to parse natural language query');
        }

        $rows = $this->getAll($filters);

        // decode properties
        foreach ($rows as &$row) {
            if (isset($row['properties']) && is_string($row['properties'])) {
                $row['properties'] = json_decode($row['properties'], true);
            }
        }

        return [
            'data' => $rows,
            'count' => count($rows),
            'interpreted_query' => [
                'original' => $query,
                'parsed_filters' => $filters
            ]
        ];
    }

    public function parseNaturalLanguageQuery(string $query): array {
        $filters = [];

        if (preg_match('/single word/i', $query)) $filters['word_count'] = 1;
        if (preg_match('/palindromic/i', $query)) $filters['is_palindrome'] = true;
        if (preg_match('/longer than (\d+)/i', $query, $m)) $filters['min_length'] = (int)$m[1] + 1;
        if (preg_match('/shorter than (\d+)/i', $query, $m)) $filters['max_length'] = (int)$m[1] - 1;
        if (preg_match('/containing the letter (\w)/i', $query, $m)) $filters['contains_character'] = $m[1];

        return $filters;
    }

    // ---- Helper Methods ----

    private function splitChars(string $value): array {
        return preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function wordCount(string $value): int {
        $words = preg_split('/\s+/u', trim($value));
        return $words && $words[0] !== '' ? count($words) : 0;
    }

    private function mb_strrev(string $str): string {
        return implode('', array_reverse($this->splitChars($str)));
    }
}
