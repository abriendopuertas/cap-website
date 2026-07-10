<?php
require_once __DIR__ . '/_funciones.php';
requireLogin();
$titulo = $titulo ?? 'Admin';
$flash_msg = flashGet();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo) ?> — CAP</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5; color: #333; min-height: 100vh;
        }
        .topbar {
            background: #1a1a2e; color: #fff; padding: 0 30px; display: flex;
            align-items: center; justify-content: space-between; height: 56px;
        }
        .topbar .brand { font-weight: 700; font-size: 16px; color: #6EBE44; }
        .topbar .brand span { color: #888; font-weight: 400; }
        .topbar .user-info { font-size: 13px; color: #999; }
        .topbar .user-info a { color: #6EBE44; text-decoration: none; margin-left: 15px; }
        .topbar .user-info a:hover { text-decoration: underline; }
        .nav {
            background: #fff; border-bottom: 1px solid #ddd; padding: 0 20px;
            display: flex; gap: 0; overflow-x: auto;
        }
        .nav a {
            padding: 14px 18px; text-decoration: none; color: #666; font-size: 14px;
            border-bottom: 2px solid transparent; white-space: nowrap; transition: 0.15s;
        }
        .nav a:hover, .nav a.active { color: #6EBE44; border-bottom-color: #6EBE44; }
        .content { padding: 25px 30px; max-width: 1100px; margin: 0 auto; }
        .flash {
            padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
        }
        .flash.success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4caf50; }
        .flash.error { background: #ffebee; color: #c62828; border-left: 4px solid #ef5350; }
        .flash.info { background: #e3f2fd; color: #1565c0; border-left: 4px solid #42a5f5; }
        .nav .active { color: #6EBE44; border-bottom-color: #6EBE44; font-weight: 600; }
</style>
</head>
<body>
    <div class="topbar">
        <div class="brand">CAP <span>| Administración</span></div>
        <div class="user-info">
            <?= h($_SESSION['admin']['nombre'] ?? 'Admin') ?>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </div>
    <div class="nav">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="textos.php">📝 Editar Páginas</a>
        <a href="noticias.php">📰 Noticias</a>
        <a href="paginas.php">📄 Páginas</a>
        <a href="config.php">⚙️ Config</a>
        <a href="publicar.php">🚀 Publicar</a>
    </div>
    <div class="content">
    <?php if ($flash_msg): ?>
        <div class="flash <?= h($flash_msg['t']) ?>"><?= h($flash_msg['m']) ?></div>
    <?php endif; ?>
