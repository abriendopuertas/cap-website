<?php
session_start();
define('DATA_DIR', __DIR__ . '/data');
define('PLANTILLAS_DIR', __DIR__ . '/plantillas');
define('SITE_DIR', __DIR__ . '/..');
define('UPLOADS_DIR', SITE_DIR . '/uploads/equipo');

function login($user, $pass) {
    $users = json_decode(file_get_contents(DATA_DIR . '/usuarios.json'), true);
    if (isset($users[$user]) && password_verify($pass, $users[$user]['password_hash'])) {
        $_SESSION['admin'] = [
            'user' => $user, 'nombre' => $users[$user]['nombre'], 'rol' => $users[$user]['rol']
        ];
        return true;
    }
    return false;
}
function isLogged() { return isset($_SESSION['admin']); }
function requireLogin() { if (!isLogged()) { header('Location: index.php'); exit; } }
function dataGet($file) {
    $p = DATA_DIR . "/$file.json";
    return file_exists($p) ? (json_decode(file_get_contents($p), true) ?: []) : [];
}
function dataSave($file, $data) {
    $p = DATA_DIR . "/$file.json";
    $bk = DATA_DIR . "/backups";
    if (!is_dir($bk)) mkdir($bk, 0755, true);
    if (file_exists($p)) copy($p, "$bk/{$file}_" . date('Ymd_His') . '.json');
    return file_put_contents($p, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function csrf() {
    $t = bin2hex(random_bytes(32));
    $_SESSION['csrf'] = $t;
    return '<input type="hidden" name="csrf" value="' . $t . '">';
}
function checkCsrf() {
    if (!isset($_POST['csrf']) || !isset($_SESSION['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        die('Error de seguridad.');
    }
}
function flash($msg, $type = 'success') { $_SESSION['flash'] = ['m' => $msg, 't' => $type]; }
function flashGet() {
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

/** Get the display URL for a member image (local upload or Wix) */
function memberImgUrl($path) {
    if (empty($path)) return '';
    if (preg_match('/^https?:\/\//', $path)) return $path;
    if (strpos($path, 'uploads/') === 0) return '../' . $path;
    if (strpos($path, 'images/') === 0) return '../' . $path;
    return '../images/placeholder.png';  // Fallback (was Wix)
}

/** Get the filesystem path for an uploaded image (or false if not local) */
function memberImgPath($path) {
    if (empty($path)) return false;
    if (strpos($path, 'uploads/') === 0) return SITE_DIR . '/' . $path;
    return false;
}
