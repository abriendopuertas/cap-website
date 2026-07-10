<?php
define('DB_PATH', __DIR__ . '/../data/site.db');
define('SITE_DIR', __DIR__ . '/..');
define('UPLOADS_DIR', SITE_DIR . '/uploads');

function db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

    $isNew = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');

    if ($isNew) initSchema($pdo);
    return $pdo;
}

function initSchema($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            name TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );

        CREATE TABLE IF NOT EXISTS news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            date TEXT,
            image TEXT,
            excerpt TEXT,
            body TEXT,
            active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS page_content (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page TEXT NOT NULL,
            field TEXT NOT NULL,
            value TEXT,
            field_type TEXT DEFAULT 'text',
            label TEXT,
            sort_order INTEGER DEFAULT 0,
            UNIQUE(page, field)
        );

        CREATE TABLE IF NOT EXISTS downloads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            section TEXT NOT NULL DEFAULT 'memorias',
            title TEXT NOT NULL,
            file_path TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS media (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            original_name TEXT,
            alt_text TEXT,
            uploaded_at TEXT DEFAULT (datetime('now'))
        );
    ");

    // Default admin user: admin / admin123
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT OR IGNORE INTO users (username, password_hash, name) VALUES (?, ?, ?)")
        ->execute(['admin', $hash, 'Administrador']);

    // Default settings
    $defaults = [
        'site_name' => 'Corporación Abriendo Puertas',
        'email' => 'capcoordinador@gmail.com',
        'phone1' => '+562 2715 1262',
        'phone2' => '+569 6238 0925',
        'address' => 'Capitán Prat 20, San Joaquín, Santiago',
        'facebook' => 'https://www.facebook.com/abriendopuertascap',
        'instagram' => 'https://www.instagram.com/abriendopuertas.cap',
        'donate_url' => 'https://abriendopuertas.donando.cl/',
        'copyright' => '© 2025 Corporación Abriendo Puertas',
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) $stmt->execute([$k, $v]);
}

function setting($key, $default = '') {
    $stmt = db()->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function settingSave($key, $value) {
    db()->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)")
        ->execute([$key, $value]);
}

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
