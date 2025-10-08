<?php
// One-time helper: set a user's password (bcrypt) by email or name.
// Usage (local only):
// http://localhost/organic_fruits&vegetables/admin/set_admin_password.php?email=admin@local.test&password=admin123
// or
// http://localhost/organic_fruits&vegetables/admin/set_admin_password.php?name="Site%20Admin"&password=admin123

require_once __DIR__ . '/includes/db.php';

// Restrict to local only (allow IPv4, IPv6 loopback and IPv6-mapped IPv4)
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', '::ffff:127.0.0.1'])) {
    http_response_code(403);
    $addr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    echo "Access denied. Run this locally. Your REMOTE_ADDR: " . htmlspecialchars($addr);
    exit;
}

// Accept parameters: email (preferred) or name, password (required), phone, role, status
$email = trim($_GET['email'] ?? '');
$name = trim($_GET['name'] ?? '');
$password = $_GET['password'] ?? null;
$phone = $_GET['phone'] ?? null;
$role = $_GET['role'] ?? null;
$status = $_GET['status'] ?? null; // account_status

if (!$password || (!$email && !$name)) {
    echo "Usage: ?email=admin@local.test&password=admin123&name=\"Site Admin\"&phone=123&role=admin&status=active\n";
    echo "You can also omit name when updating an existing email. This script MUST be run locally.\n";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// If email provided, try to find user by email
if ($email) {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        // update existing user with provided fields
        $fields = ['password_hash' => $hash];
        $params = [$hash];
        $sqlParts = ['password_hash = ?'];
        if ($name !== '') { $sqlParts[] = 'name = ?'; $params[] = $name; }
        if ($phone !== null) { $sqlParts[] = 'phone = ?'; $params[] = $phone; }
        if ($role !== null) { $sqlParts[] = 'role = ?'; $params[] = $role; }
        if ($status !== null) { $sqlParts[] = 'account_status = ?'; $params[] = $status; }
        $params[] = $email;
        $sql = 'UPDATE users SET ' . implode(', ', $sqlParts) . ' WHERE email = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();
        echo "Updated $count existing user(s) with email=$email.\n";
    } else {
        // insert new user
        $insertName = $name ?: 'Admin';
        $insertPhone = $phone ?: null;
        $insertRole = $role ?: 'admin';
        $insertStatus = $status ?: 'active';
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,phone,role,account_status) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$insertName, $email, $hash, $insertPhone, $insertRole, $insertStatus]);
        $id = $pdo->lastInsertId();
        echo "Inserted new user id=$id with email=$email.\n";
    }
} else {
    // No email, use name to update password (legacy behavior)
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $fields = ['password_hash' => $hash];
        $params = [$hash];
        $sqlParts = ['password_hash = ?'];
        if ($phone !== null) { $sqlParts[] = 'phone = ?'; $params[] = $phone; }
        if ($role !== null) { $sqlParts[] = 'role = ?'; $params[] = $role; }
        if ($status !== null) { $sqlParts[] = 'account_status = ?'; $params[] = $status; }
        $params[] = $name;
        $sql = 'UPDATE users SET ' . implode(', ', $sqlParts) . ' WHERE name = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();
        echo "Updated $count existing user(s) with name=$name.\n";
    } else {
        // create using name only (no email)
        $insertPhone = $phone ?: null;
        $insertRole = $role ?: 'admin';
        $insertStatus = $status ?: 'active';
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,phone,role,account_status) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$name, null, $hash, $insertPhone, $insertRole, $insertStatus]);
        $id = $pdo->lastInsertId();
        echo "Inserted new user id=$id with name=$name.\n";
    }
}

echo "Done. Please delete this file after use.\n";
