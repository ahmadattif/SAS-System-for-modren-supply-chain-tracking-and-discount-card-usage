<?php
// ════════════════════════════════════════════════════════════════════
//  SENTINEL SAS v4.1 — Core Database Connection Engine (MySQLi Bridge)
//  Credentials & Recognition:
//  Muhammad Ahmad Atif, 4th Sem BS AI — IMSC University
// ════════════════════════════════════════════════════════════════════

/**
 * Establishes and returns a synchronized MySQLi connection instance
 * for the Sentinel SAS Core API architecture layers.
 * * @return mysqli
 */
function getDB(): mysqli {
    $servername = "localhost";
    $username   = "root";
    $password   = ""; // Default XAMPP password is empty
    $dbname     = "sentinel_sas"; // Your exact database container name

    // Enable explicit internal mysqli exception handling
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Construct standard structural object-oriented MySQLi bridge connection
        $db = new mysqli($servername, $username, $password, $dbname);
        
        // Match charset configuration ecosystem with the schema structure
        $db->set_charset("utf8mb4");
        
        return $db;
        
    } catch (Exception $e) {
        // Fallback structural wrapper to capture connection drops safely
        http_response_code(500);
        die(json_encode([
            "success" => false,
            "message" => "CRITICAL CORE FAILURE: Database API bridge layer broke. " . $e->getMessage()
        ]));
    }
}