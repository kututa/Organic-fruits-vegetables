<?php
session_start();
require_once __DIR__ . '/db.php';

function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function require_admin() {
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

// login function
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $login_input = trim($_POST['username'] ?? ''); // accepts name or email
    $password = $_POST['password'] ?? '';

    if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT user_id AS id, name AS username, password_hash AS password, role, email, account_status AS status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$login_input]);
    } else {
        // login by name
        $stmt = $pdo->prepare('SELECT user_id AS id, name AS username, password_hash AS password, role, email, account_status AS status FROM users WHERE name = ? LIMIT 1');
        $stmt->execute([$login_input]);
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
        // remove password from session for safety
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    } else {
        $login_error = 'Invalid credentials or inactive user.';
    }
}

// registration (local only) — create a new admin/user so you can sign up locally
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_action'])) {
    // Prevent public registration in production — allow only local hosts
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1', '::ffff:127.0.0.1'])) {
        $register_error = 'Registration is allowed only from the local machine.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $register_error = 'Name, email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $register_error = 'Please provide a valid email address.';
        } elseif ($password !== $password_confirm) {
            $register_error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $register_error = 'Password must be at least 6 characters.';
        } else {
            // check existing
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($exists) {
                $register_error = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // default to admin role for local convenience — remove in production
                $role = 'admin';
                $status = 'active';
                $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,phone,role,account_status) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$name, $email, $hash, null, $role, $status]);
                $register_success = 'Account created. You can now log in.';
            }
        }
    }
}
