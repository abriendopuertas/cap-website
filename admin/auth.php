<?php
session_start();
require_once __DIR__ . '/db.php';

function login($username, $password) {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name']
        ];
        return true;
    }
    return false;
}

function isLogged() {
    return isset($_SESSION['admin_user']);
}

function requireLogin() {
    if (!isLogged()) {
        header('Location: index.php');
        exit;
    }
}

function currentUser() {
    return $_SESSION['admin_user'] ?? null;
}

function csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf_token'] . '">';
}

function checkCsrf() {
    if (!isset($_POST['_csrf']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_csrf'])) {
        http_response_code(403);
        die('Token de seguridad inválido.');
    }
}

function flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
