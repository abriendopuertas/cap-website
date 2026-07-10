<?php
require_once __DIR__ . '/auth.php';

if (isLogged()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['user'] ?? '', $_POST['pass'] ?? '')) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso — CAP Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',system-ui,sans-serif;
            background:linear-gradient(135deg,#6EBE44 0%,#4a8f2d 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
        }
        .box {
            background:#fff; border-radius:16px; padding:45px 40px; width:380px;
            box-shadow:0 25px 60px rgba(0,0,0,0.15);
        }
        .box .brand { color:#6EBE44; font-weight:800; font-size:28px; margin-bottom:4px; }
        .box h1 { font-size:20px; color:#333; margin-bottom:4px; }
        .box p { color:#888; font-size:14px; margin-bottom:25px; }
        .box label { display:block; font-size:14px; color:#555; margin-bottom:5px; font-weight:500; }
        .box input {
            width:100%; padding:11px 14px; border:1px solid #ddd; border-radius:8px;
            font-size:14px; margin-bottom:16px; transition:.15s;
        }
        .box input:focus { outline:none; border-color:#6EBE44; box-shadow:0 0 0 3px rgba(110,190,68,0.15); }
        .box button {
            width:100%; padding:12px; background:#6EBE44; color:#fff; border:none;
            border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; transition:.15s;
        }
        .box button:hover { background:#5aa636; }
        .error { background:#fde8e8; color:#9b1c1c; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
    </style>
</head>
<body>
<div class="box">
    <div class="brand">CAP</div>
    <h1>Panel de Administración</h1>
    <p>Ingresa para gestionar el sitio web</p>
    <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST">
        <label>Usuario</label>
        <input type="text" name="user" required autofocus>
        <label>Contraseña</label>
        <input type="password" name="pass" required>
        <button type="submit">Ingresar</button>
    </form>
</div>
</body>
</html>
