<?php
// ─── DB CONNECTION CONFIG ────────────────────────────────────────────────────
// Edit these to match your MySQL/MariaDB setup
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // ← your DB password
define('DB_NAME', 'sentinel_sas');
define('DB_PORT', 3306);

/**
 * Returns a live MySQLi connection. On failure, sends a 500 JSON response and exits.
 */
function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
