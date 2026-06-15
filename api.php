<?php
// ════════════════════════════════════════════════════════════════════════════
//  SENTINEL SAS v4.2 — API Endpoint Engine
//  All frontend JS communicates with this single file via POST (JSON body).
//  Expects: { "action": "<action_name>", ...params }
//
//  Credentials & Recognition:
//  Muhammad Ahmad Atif, 4th Sem BS AI — IMSC University
// ════════════════════════════════════════════════════════════════════════════

declare(strict_types=1);

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? '');

switch ($action) {
    case 'login':           handleLogin($body);         break;
    case 'logout':          handleLogout();             break;
    case 'check_session':   checkSession();             break;
    case 'get_suppliers':   getSuppliers();             break;
    case 'get_owner_stats': getOwnerStats();            break;
    case 'get_flagged_hashes': getFlaggedHashes();      break;
    case 'validate_hash':   validateHash($body);        break;
    case 'submit_defect':   submitDefect($body);        break;
    case 'authorize_node':  authorizeNode($body);       break;
    default:
        respond(false, 'Unknown action: ' . htmlspecialchars($action));
}

function respond(bool $ok, string $msg = '', $data = null): never {
    $out = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        respond(false, 'Not authenticated. Please log in.');
    }
}

function requireRole(string $role): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        respond(false, "Access denied. This endpoint requires the '{$role}' role.");
    }
}

function handleLogin(array $body): never {
    $username = trim($body['username'] ?? '');
    $password = $body['password']  ?? '';
    $rawRole  = strtoupper(trim($body['role'] ?? ''));   

    if ($username === '' || $password === '') {
        respond(false, 'Username and password are required.');
    }

    // Bulletproof Role Normalization from Frontend Console layout dropdown text
    $roleSel = '';
    if (strpos($rawRole, 'CUSTOMER') !== false) {
        $roleSel = 'Customer';
    } elseif (strpos($rawRole, 'OPERATOR') !== false || strpos($rawRole, 'EMPLOYEE') !== false) {
        $roleSel = 'Employee';
    } elseif (strpos($rawRole, 'OWNER') !== false || strpos($rawRole, 'ADMIN') !== false) {
        $roleSel = 'Owner';
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id, password_hash, role FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($userId, $hash, $dbRole);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || !password_verify($password, (string)$hash)) {
        respond(false, 'Invalid credentials. Check your username and password.');
    }

    if ($roleSel !== '' && $roleSel !== $dbRole) {
        respond(false, "Access domain mismatch. Your account is registered as '{$dbRole}', not '{$roleSel}'.");
    }

    session_regenerate_id(true);
    $_SESSION['user_id']  = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = $dbRole;

    $payload = ['role' => $dbRole, 'username' => $username];

    if ($dbRole === 'Customer') {
        $payload = array_merge($payload, fetchCustomerData($db, $userId));
    } elseif ($dbRole === 'Employee') {
        $payload = array_merge($payload, fetchEmployeeData($db, $userId));
        $_SESSION['employee_id'] = $payload['employee_db_id'] ?? null;
    }

    $db->close();
    respond(true, 'Login successful.', $payload);
}

function handleLogout(): never {
    session_unset();
    session_destroy();
    respond(true, 'Session terminated.');
}

function checkSession(): never {
    if (empty($_SESSION['user_id'])) {
        respond(false, 'No active session.');
    }
    respond(true, 'Session active.', [
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
    ]);
}

function fetchCustomerData(mysqli $db, int $userId): array {
    $stmt = $db->prepare(
        'SELECT c.id, c.member_suffix, c.tier, c.discount, c.status,
                c.balance, c.total_spent, c.billing_date
         FROM customers c
         WHERE c.user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cust   = $result->fetch_assoc();
    $stmt->close();

    if (!$cust) return ['error' => 'Customer profile not found.'];

    $stmt2 = $db->prepare(
        'SELECT asset_hash, item_description, transaction_ref,
                DATE_FORMAT(purchase_date, "%Y-%m-%d") AS purchase_date, net_amount
         FROM purchase_history
         WHERE customer_id = ?
         ORDER BY purchase_date DESC'
    );
    $stmt2->bind_param('i', $cust['id']);
    $stmt2->execute();
    $history = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    return [
        'customer_db_id' => (int)$cust['id'],
        'member_suffix'  => $cust['member_suffix'],
        'tier'           => $cust['tier'],
        'discount'       => (int)$cust['discount'],
        'status'         => $cust['status'],
        'balance'        => (float)$cust['balance'],
        'total_spent'    => (float)$cust['total_spent'],
        'billing_date'   => $cust['billing_date'],
        'history'        => $history,
    ];
}

function fetchEmployeeData(mysqli $db, int $userId): array {
    $stmt = $db->prepare(
        'SELECT id, role_title, node_id, tx_count, defect_count
         FROM employees WHERE user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $emp    = $result->fetch_assoc();
    $stmt->close();

    if (!$emp) return ['error' => 'Employee profile not found.'];

    return [
        'employee_db_id' => (int)$emp['id'],
        'role_title'     => $emp['role_title'],
        'node_id'        => $emp['node_id'],
        'tx_count'       => (int)$emp['tx_count'],
        'defect_count'   => (int)$emp['defect_count'],
    ];
}

function getSuppliers(): never {
    requireAuth();
    $db   = getDB();
    $res  = $db->query(
        'SELECT hash_prefix, name, country, units_supplied, defect_count,
                CAST(integrity_score AS CHAR) AS integrity_score, status
         FROM suppliers ORDER BY integrity_score ASC'
    );
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $db->close();

    foreach ($rows as &$r) {
        $r['units_supplied'] = (int)$r['units_supplied'];
        $r['defect_count']   = (int)$r['defect_count'];
        $r['integrity_score'] = (float)$r['integrity_score'];
    }
    respond(true, '', $rows);
}

function getOwnerStats(): never {
    requireRole('Owner');
    $db = getDB();

    $rev = $db->query('SELECT COALESCE(SUM(net_amount),0) AS total FROM purchase_history')
               ->fetch_assoc()['total'];

    $flagged = (int)$db->query('SELECT COUNT(*) AS c FROM flagged_hashes')->fetch_assoc()['c'];

    $restricted = (int)$db->query("SELECT COUNT(*) AS c FROM suppliers WHERE status='Restricted'")->fetch_assoc()['c'];

    $db->close();
    respond(true, '', [
        'revenue'    => (float)$rev,
        'flagged'    => $flagged,
        'restricted' => $restricted,
    ]);
}

function getFlaggedHashes(): never {
    requireRole('Owner');
    $db   = getDB();
    $res  = $db->query(
        "SELECT asset_hash, reason,
                DATE_FORMAT(flagged_at, '%Y-%m-%d %H:%i') AS flagged_at
         FROM flagged_hashes ORDER BY flagged_at DESC LIMIT 50"
    );
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $db->close();
    respond(true, '', $rows);
}

function validateHash(array $body): never {
    requireRole('Employee');

    $hash = strtoupper(trim($body['hash'] ?? ''));

    if (!preg_match('/^[0-9A-F]{16}$/', $hash)) {
        respond(false, 'Hash must be exactly 16 hexadecimal characters.');
    }

    $db       = getDB();
    $prefix   = substr($hash, 0, 4); 
    $empId    = (int)($_SESSION['employee_id'] ?? 0);

    $stmt = $db->prepare(
        'SELECT id, name, status FROM suppliers WHERE hash_prefix = ?'
    );
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $sup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($empId > 0) {
        $db->query("UPDATE employees SET tx_count = tx_count + 1 WHERE id = {$empId}");
    }

    $stmt2 = $db->prepare('SELECT id FROM purchase_history WHERE asset_hash = ?');
    $stmt2->bind_param('s', $hash);
    $stmt2->execute();
    $existing = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    if ($existing && $sup && $sup['status'] === 'Restricted') {
        $reason = "Validation attempt on known hash {$hash} — supplier '{$sup['name']}' RESTRICTED";
        $stmtF  = $db->prepare('INSERT INTO flagged_hashes (asset_hash, reason) VALUES (?,?)');
        $stmtF->bind_param('ss', $hash, $reason);
        $stmtF->execute();
        $stmtF->close();
    }

    $db->close();

    respond(true, 'Hash validated.', [
        'hash'       => $hash,
        'vendor_name'=> $sup['name'] ?? null,
        'restricted' => ($sup['status'] ?? '') === 'Restricted',
        'duplicate'  => (bool)$existing,
    ]);
}

function submitDefect(array $body): never {
    requireRole('Employee');

    $hash     = strtoupper(trim($body['hash']     ?? ''));
    $severity = trim($body['severity'] ?? '');
    $notes    = trim($body['notes']    ?? '');
    $empId    = (int)($_SESSION['employee_id'] ?? 0);

    if (!preg_match('/^[0-9A-F]{12}$/', $hash)) {
        respond(false, 'Unit hash must be exactly 12 valid hex characters.');
    }

    $validSeverities = ['Minor', 'Major', 'Critical'];
    if (!in_array($severity, $validSeverities, true)) {
        respond(false, 'Invalid severity level.');
    }

    if ($empId === 0) {
        respond(false, 'Employee session not found. Please log in again.');
    }

    $db     = getDB();
    $prefix = substr($hash, 0, 4);

    $stmt = $db->prepare(
        'SELECT id FROM suppliers WHERE hash_prefix = ?'
    );
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $sup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sup) {
        $db->close();
        respond(false, 'Unknown supplier prefix sequence. Log registration terminated.');
    }

    $supId = (int)$sup['id'];

    $stmt2 = $db->prepare(
        'INSERT INTO defect_log (unit_hash, severity, notes, supplier_id, employee_id)
         VALUES (?,?,?,?,?)'
    );
    $stmt2->bind_param('sssii', $hash, $severity, $notes, $supId, $empId);
    $stmt2->execute();
    $stmt2->close();

    $db->query("UPDATE employees SET defect_count = defect_count + 1 WHERE id = {$empId}");

    // Let the database trigger do its job, then fetch the result
    $stmt4 = $db->prepare(
        'SELECT hash_prefix, name, country, units_supplied, defect_count,
                CAST(integrity_score AS CHAR) AS integrity_score, status
         FROM suppliers WHERE id = ?'
    );
    $stmt4->bind_param('i', $supId);
    $stmt4->execute();
    $updatedSup = $stmt4->get_result()->fetch_assoc();
    $stmt4->close();

    $updatedSup['units_supplied'] = (int)$updatedSup['units_supplied'];
    $updatedSup['defect_count']   = (int)$updatedSup['defect_count'];
    $updatedSup['integrity_score'] = (float)$updatedSup['integrity_score'];

    $db->close();

    respond(true, 'Defect recorded and supplier score updated via engine trigger.', [
        'supplier'         => $updatedSup,
        'newly_restricted' => ($updatedSup['status'] === 'Restricted'),
    ]);
}

function authorizeNode(array $body): never {
    requireAuth();
    $currentRole = $_SESSION['role'] ?? '';
    if ($currentRole !== 'Employee' && $currentRole !== 'Owner') {
        http_response_code(403);
        respond(false, "Access denied. Node bindings are restricted to authorized operational personnel.");
    }

    // Fallback alignment: Catch the input whether the frontend calls it 'password', 'passcode', or 'confirmation'
    $password = $body['password'] ?? $body['passcode'] ?? $body['confirmation'] ?? '';
    $password = trim((string)$password);

    $empId    = (int)($_SESSION['employee_id'] ?? 0);
    $userId   = (int)($_SESSION['user_id']     ?? 0);

    if ($password === '') {
        respond(false, 'Confirmation security passcode input layer is blank.');
    }

    $db = getDB();

    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($storedHash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($password, $storedHash ?? '')) {
        $db->close();
        // Custom error tag to verify exactly which parameter matched
        respond(false, "Invalid credentials. Code authentication mismatch on user ID context #{$userId}.");
    }

    // Determine target Node ID depending on context
    $nodeId = 'NODE-OWNER-CORE'; 
    if ($currentRole === 'Employee' && $empId > 0) {
        $stmt2 = $db->prepare('SELECT node_id FROM employees WHERE id = ?');
        $stmt2->bind_param('i', $empId);
        $stmt2->execute();
        $stmt2->bind_result($fetchedNodeId);
        $fetchedNodeId = $stmt2->fetch() ? $fetchedNodeId : '';
        $stmt2->close();
        if (!empty($fetchedNodeId)) {
            $nodeId = $fetchedNodeId;
        }
    }

    // Write authorization log token entry safely
    $stmt3 = $db->prepare(
        'INSERT INTO node_authorizations (employee_id, node_id) VALUES (?,?)'
    );
    
    // Bind null context variable cleanly for SQL matching
    $boundEmpId = ($currentRole === 'Owner') ? null : $empId;
    
    // Use types 'is' if matching signed structure or handle fallback manually
    if ($boundEmpId === null) {
        // Direct execute to bypass binding errors for structural owners
        $db->query("INSERT INTO node_authorizations (employee_id, node_id) VALUES (NULL, '{$nodeId}')");
    } else {
        $stmt3->bind_param('is', $boundEmpId, $nodeId);
        $stmt3->execute();
    }
    $stmt3->close();

    $db->close();
    respond(true, 'Node authorized and bound to operator profile.', ['node_id' => $nodeId]);
}