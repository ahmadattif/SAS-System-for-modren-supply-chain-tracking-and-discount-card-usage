<?php
// ════════════════════════════════════════════════════════════════════════════
//  SENTINEL SAS v4.1 — API Endpoint
//  All frontend JS communicates with this single file via POST (JSON body).
//  Expects: { "action": "<action_name>", ...params }
// ════════════════════════════════════════════════════════════════════════════

declare(strict_types=1);

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db.php';

// ── Parse request ────────────────────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? '');

// ── Route ────────────────────────────────────────────────────────────────────
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

// ════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════════════════════════════

/** Send JSON response and exit. */
function respond(bool $ok, string $msg = '', $data = null): never {
    $out = ['success' => $ok, 'message' => $msg];
    if ($data !== null) $out['data'] = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Require an authenticated session; exits with 401 if not authenticated. */
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        respond(false, 'Not authenticated. Please log in.');
    }
}

/** Require a specific role (case-sensitive). */
function requireRole(string $role): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        respond(false, "Access denied. This endpoint requires the '{$role}' role.");
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  AUTH ACTIONS
// ════════════════════════════════════════════════════════════════════════════

function handleLogin(array $body): never {
    $username = trim($body['username'] ?? '');
    $password = $body['password']  ?? '';
    $roleSel  = trim($body['role'] ?? '');   // role selected by user in dropdown

    if ($username === '' || $password === '') {
        respond(false, 'Username and password are required.');
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id, password_hash, role FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($userId, $hash, $dbRole);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || !password_verify($password, $hash)) {
        respond(false, 'Invalid credentials. Check your username and password.');
    }

    // Validate that the chosen domain matches the stored role
    if ($roleSel !== '' && $roleSel !== $dbRole) {
        respond(false, "Access domain mismatch. Your account is registered as '{$dbRole}', not '{$roleSel}'.");
    }

    // Build session
    session_regenerate_id(true);
    $_SESSION['user_id']  = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = $dbRole;

    $payload = ['role' => $dbRole, 'username' => $username];

    // Attach role-specific data
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

// ════════════════════════════════════════════════════════════════════════════
//  CUSTOMER
// ════════════════════════════════════════════════════════════════════════════

/** Returns the full customer profile + purchase history for the given user_id. */
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

    // Purchase history
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

// ════════════════════════════════════════════════════════════════════════════
//  EMPLOYEE
// ════════════════════════════════════════════════════════════════════════════

/** Returns the employee profile for the given user_id. */
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

// ════════════════════════════════════════════════════════════════════════════
//  SUPPLIERS  (shared by Employee hash lookup and Owner table)
// ════════════════════════════════════════════════════════════════════════════

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

    // Cast numeric fields
    foreach ($rows as &$r) {
        $r['units_supplied'] = (int)$r['units_supplied'];
        $r['defect_count']   = (int)$r['defect_count'];
        $r['integrity_score'] = (float)$r['integrity_score'];
    }
    respond(true, '', $rows);
}

// ════════════════════════════════════════════════════════════════════════════
//  OWNER
// ════════════════════════════════════════════════════════════════════════════

function getOwnerStats(): never {
    requireRole('Owner');
    $db = getDB();

    // Total revenue from all purchases
    $rev = $db->query('SELECT COALESCE(SUM(net_amount),0) AS total FROM purchase_history')
               ->fetch_assoc()['total'];

    // Count flagged hashes
    $flagged = (int)$db->query('SELECT COUNT(*) AS c FROM flagged_hashes')->fetch_assoc()['c'];

    // Count restricted vendors
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

// ════════════════════════════════════════════════════════════════════════════
//  HASH VALIDATION  (Employee)
// ════════════════════════════════════════════════════════════════════════════

function validateHash(array $body): never {
    requireRole('Employee');

    $hash = strtoupper(trim($body['hash'] ?? ''));

    if (!preg_match('/^[0-9A-F]{16}$/', $hash)) {
        respond(false, 'Hash must be exactly 16 hexadecimal characters.');
    }

    $db       = getDB();
    $prefix   = substr($hash, 0, 4);
    $empId    = (int)($_SESSION['employee_id'] ?? 0);

    // Lookup supplier
    $stmt = $db->prepare(
        'SELECT id, name, status FROM suppliers WHERE hash_prefix = ?'
    );
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $sup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Increment employee tx_count
    if ($empId > 0) {
        $db->query("UPDATE employees SET tx_count = tx_count + 1 WHERE id = {$empId}");
    }

    // Check if this hash is in purchase_history (duplicate check)
    $stmt2 = $db->prepare('SELECT id FROM purchase_history WHERE asset_hash = ?');
    $stmt2->bind_param('s', $hash);
    $stmt2->execute();
    $existing = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    // If hash exists and supplier is restricted → auto-flag
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

// ════════════════════════════════════════════════════════════════════════════
//  DEFECT SUBMISSION  (Employee)
// ════════════════════════════════════════════════════════════════════════════

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

    // Fetch supplier
    $stmt = $db->prepare(
        'SELECT id, name, units_supplied, defect_count, integrity_score, status
         FROM suppliers WHERE hash_prefix = ?'
    );
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $sup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $supId = $sup ? (int)$sup['id'] : null;

    // Insert defect log
    $stmt2 = $db->prepare(
        'INSERT INTO defect_log (unit_hash, severity, notes, supplier_id, employee_id)
         VALUES (?,?,?,?,?)'
    );
    $stmt2->bind_param('sssii', $hash, $severity, $notes, $supId, $empId);
    $stmt2->execute();
    $stmt2->close();

    // Increment employee defect count
    $db->query("UPDATE employees SET defect_count = defect_count + 1 WHERE id = {$empId}");

    $newlyRestricted = false;

    // Update supplier stats
    if ($sup) {
        $newUnits   = (int)$sup['units_supplied'] + 1;
        $newDefects = (int)$sup['defect_count'] + 1;
        $newScore   = max(0, round(100 - ($newDefects / $newUnits * 100), 2));
        $prevStatus = $sup['status'];
        $newStatus  = $newScore < 85.0 ? 'Restricted' : 'Active';

        $stmt3 = $db->prepare(
            'UPDATE suppliers
             SET units_supplied=?, defect_count=?, integrity_score=?, status=?
             WHERE id=?'
        );
        $stmt3->bind_param('iidsi', $newUnits, $newDefects, $newScore, $newStatus, $supId);
        $stmt3->execute();
        $stmt3->close();

        $newlyRestricted = ($prevStatus === 'Active' && $newStatus === 'Restricted');

        // If newly restricted, log a flag
        if ($newlyRestricted) {
            $reason = "Supplier '{$sup['name']}' integrity score dropped to {$newScore}% — auto-restricted after defect log.";
            $stmtF  = $db->prepare('INSERT INTO flagged_hashes (asset_hash, reason) VALUES (?,?)');
            $flagHash = $hash . 'XXXX'; // partial hash reference
            $stmtF->bind_param('ss', $flagHash, $reason);
            $stmtF->execute();
            $stmtF->close();
        }

        // Re-fetch updated supplier
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
    }

    $db->close();

    respond(true, 'Defect recorded and supplier score updated.', [
        'supplier'         => $updatedSup ?? null,
        'newly_restricted' => $newlyRestricted,
    ]);
}

// ════════════════════════════════════════════════════════════════════════════
//  NODE AUTHORIZATION  (Employee — first-login modal)
// ════════════════════════════════════════════════════════════════════════════

function authorizeNode(array $body): never {
    requireRole('Employee');

    $password = $body['password'] ?? '';
    $empId    = (int)($_SESSION['employee_id'] ?? 0);
    $userId   = (int)($_SESSION['user_id']     ?? 0);

    if ($password === '') {
        respond(false, 'Biometric confirmation code is required.');
    }

    $db = getDB();

    // Verify password matches user's stored hash
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($storedHash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($password, $storedHash ?? '')) {
        $db->close();
        respond(false, 'Biometric code mismatch. Authorization logged but not confirmed.');
    }

    // Fetch node_id
    $stmt2 = $db->prepare('SELECT node_id FROM employees WHERE id = ?');
    $stmt2->bind_param('i', $empId);
    $stmt2->execute();
    $stmt2->bind_result($nodeId);
    $stmt2->fetch();
    $stmt2->close();

    // Log authorization
    $stmt3 = $db->prepare(
        'INSERT INTO node_authorizations (employee_id, node_id) VALUES (?,?)'
    );
    $stmt3->bind_param('is', $empId, $nodeId);
    $stmt3->execute();
    $stmt3->close();

    $db->close();
    respond(true, 'Node authorized and bound to operator profile.', ['node_id' => $nodeId]);
}
