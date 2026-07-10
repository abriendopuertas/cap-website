<?php
function layoutStart($title = 'Admin') {
    $user = currentUser();
    $flash = getFlash();
    $current = basename($_SERVER['SCRIPT_NAME']);
    ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> — CAP Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',system-ui,-apple-system,sans-serif; background:#f5f5f5; color:#333; }
        a { color:#4a8f2d; text-decoration:none; }
        a:hover { text-decoration:underline; }

        .topbar { background:#2d2d2d; color:#fff; padding:0 24px; display:flex; align-items:center; height:52px; }
        .topbar .brand { font-weight:700; color:#6EBE44; font-size:15px; margin-right:32px; }
        .topbar nav { display:flex; gap:4px; flex:1; }
        .topbar nav a { color:#ccc; font-size:13px; padding:8px 14px; border-radius:6px; transition:.15s; }
        .topbar nav a:hover, .topbar nav a.active { background:#444; color:#fff; text-decoration:none; }
        .topbar .user { font-size:13px; color:#aaa; margin-left:auto; }
        .topbar .user a { color:#6EBE44; margin-left:12px; }

        .container { max-width:1100px; margin:0 auto; padding:30px 24px; }
        h1 { font-size:24px; margin-bottom:6px; }
        h1 + p { color:#888; margin-bottom:24px; font-size:14px; }

        .flash { padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:14px; }
        .flash.success { background:#e8f5e1; color:#2d6a0f; border:1px solid #c3e6b5; }
        .flash.error { background:#fde8e8; color:#9b1c1c; border:1px solid #f5c6c6; }

        .card { background:#fff; border-radius:12px; padding:22px; border:1px solid #e8e8e8; }
        .card + .card { margin-top:16px; }

        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-bottom:24px; }
        .grid .card { display:flex; align-items:center; gap:16px; transition:.15s; cursor:pointer; }
        .grid .card:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,0,0,0.06); }
        .grid .card .icon { font-size:28px; }
        .grid .card strong { font-size:15px; display:block; }
        .grid .card span { font-size:13px; color:#888; }

        .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; border:none;
               font-size:14px; font-weight:600; cursor:pointer; transition:.15s; text-decoration:none; }
        .btn:hover { text-decoration:none; }
        .btn-primary { background:#6EBE44; color:#fff; }
        .btn-primary:hover { background:#5aa636; }
        .btn-danger { background:#dc3545; color:#fff; }
        .btn-danger:hover { background:#c82333; }
        .btn-secondary { background:#e8e8e8; color:#555; }
        .btn-secondary:hover { background:#ddd; }
        .btn-sm { padding:6px 14px; font-size:13px; }

        table { width:100%; border-collapse:collapse; }
        table th { text-align:left; font-size:12px; text-transform:uppercase; color:#888; padding:8px 12px; border-bottom:2px solid #eee; }
        table td { padding:10px 12px; border-bottom:1px solid #f0f0f0; font-size:14px; vertical-align:middle; }
        table tr:hover { background:#fafafa; }

        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        .form-group input, .form-group textarea, .form-group select {
            width:100%; padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:14px;
            font-family:inherit; transition:.15s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline:none; border-color:#6EBE44; box-shadow:0 0 0 3px rgba(110,190,68,0.15);
        }
        .form-group textarea { resize:vertical; min-height:100px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }

        .badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }
        .badge-green { background:#e8f5e1; color:#2d6a0f; }
        .badge-red { background:#fde8e8; color:#9b1c1c; }
        .badge-gray { background:#f0f0f0; color:#888; }

        .empty { text-align:center; padding:40px; color:#aaa; font-size:15px; }

        @media (max-width:768px) {
            .topbar nav { display:none; }
            .form-row { grid-template-columns:1fr; }
            .grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">CAP Admin</div>
    <nav>
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="noticias.php" class="<?= $current === 'noticias.php' ? 'active' : '' ?>">Noticias</a>
        <a href="paginas.php" class="<?= $current === 'paginas.php' ? 'active' : '' ?>">Páginas</a>
        <a href="media.php" class="<?= $current === 'media.php' ? 'active' : '' ?>">Media</a>
        <a href="settings.php" class="<?= $current === 'settings.php' ? 'active' : '' ?>">Config</a>
        <a href="usuarios.php" class="<?= $current === 'usuarios.php' ? 'active' : '' ?>">Usuarios</a>
        <a href="publicar.php" class="<?= $current === 'publicar.php' ? 'active' : '' ?>" style="<?= $current === 'publicar.php' ? '' : 'color:#6EBE44;' ?>">Publicar</a>
    </nav>
    <?php if ($user): ?>
    <div class="user"><?= h($user['name']) ?> <a href="logout.php">Salir</a></div>
    <?php endif; ?>
</div>
<div class="container">
    <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>
<?php
}

function layoutEnd() {
?>
</div>
</body>
</html>
<?php
    echo ob_get_clean();
}
