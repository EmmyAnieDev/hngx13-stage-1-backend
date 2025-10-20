<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/StringAnalyzer.php';

// simple CORS for local testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Better path extraction
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/');

// Remove base path and query string
$path = '/' . trim(substr($requestUri, strlen($basePath)), '/');
$path = strtok($path, '?');
$path = rtrim($path, '/'); // Remove trailing slash for consistency

$method = $_SERVER['REQUEST_METHOD'];

$config = include __DIR__ . '/../config.php';
$db = new DB();
$db->init();
$analyzer = new StringAnalyzer($db);

function jsonResponse($data, $status=200) {
    header('Content-Type: application/json');
    header('Accept: application/json');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

// Routing

// Health check
if ($method === 'GET' && ($path === '/' || $path === '')) {
    jsonResponse(['success' => true, 'message' => 'Api is running...'], 200);
}

// POST /strings
if ($method === 'POST' && $path === '/strings') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!is_array($body) || !isset($body['value'])) {
        jsonResponse(['error' => 'Invalid request body or missing "value" field'], 400);
    }

    if (!is_string($body['value'])) {
        jsonResponse(['error' => '"value" must be a string'], 422);
    }

    $value = $body['value'];

    try {
        $created = $analyzer->create($value);
        jsonResponse($created, 201);
    } catch (RuntimeException $e) {
        error_log("RuntimeException in create(): " . $e->getMessage());
        if ($e->getMessage() === 'String already exists.') {
            jsonResponse(['error' => 'String already exists in the system'], 409);
        }
        jsonResponse(['error' => 'Internal error'], 500);
    } catch (Exception $e) {
        error_log("Exception in create(): " . $e->getMessage());
        jsonResponse(['error' => 'Internal error'], 500);
    }
}

// GET /strings/filter-by-natural-language (MUST come before GET /strings/{string_value})
if ($method === 'GET' && $path === '/strings/filter-by-natural-language') {
    $q = isset($_GET['query']) ? $_GET['query'] : '';
    
    if ($q === '') {
        jsonResponse(['error' => 'Missing query parameter'], 400);
    }

    try {
        $result = $analyzer->filterByNaturalLanguage($q);
        jsonResponse($result, 200);
    } catch (InvalidArgumentException $e) {
        error_log("Error in /strings/filter-by-natural-language: " . $e->getMessage());
        
        jsonResponse([
            'error' => 'Unable to parse natural language query'
        ], 400);
    } catch (Exception $e) {
        error_log("Unexpected error in /strings/filter-by-natural-language: " . $e->getMessage());
        jsonResponse(['error' => 'Internal error'], 500);
    }
}

// GET /strings with filters
if ($method === 'GET' && $path === '/strings') {
    $query = $_GET;

    try {
        $result = $analyzer->getAll($query);

        // Force proper response structure
        if (!isset($result['data'])) {
            $result = [
                'data' => $result,
                'count' => is_array($result) ? count($result) : 0,
                'filters_applied' => $query
            ];
        }

        jsonResponse($result, 200);
    } catch (InvalidArgumentException $e) {
        error_log("Error in GET /strings: " . $e->getMessage());
        jsonResponse(['error' => 'Invalid query parameter values or types'], 400);
    } catch (Exception $e) {
        error_log("Unexpected error in GET /strings: " . $e->getMessage());
        jsonResponse(['error' => 'Internal error'], 500);
    }
}


// GET /strings/{string_value}
if ($method === 'GET' && preg_match('#^/strings/(.+)$#', $path, $m)) {
    $encoded = $m[1];
    $value = rawurldecode($encoded);
    $row = $analyzer->getByValue($value);
    if (!$row) jsonResponse(['error' => 'String does not exist in the system'], 404);
    jsonResponse($row, 200);
}

// DELETE /strings/{string_value}
if ($method === 'DELETE' && preg_match('#^/strings/(.+)$#', $path, $m)) {
    $value = rawurldecode($m[1]);
    $deleted = $analyzer->deleteByValue($value);
    if ($deleted) {
        http_response_code(204);
        exit;
    } else {
        jsonResponse(['error' => 'String does not exist in the system'], 404);
    }
}

// fallback
jsonResponse(['error' => 'Not found'], 404);