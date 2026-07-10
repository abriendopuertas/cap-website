<?php require_once __DIR__ . '/_funciones.php';
if (isLogged()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['user'] ?? '', $_POST['pass'] ?? '')) {
        header('Location: dashboard.php'); exit;
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
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #6EBE44 0%, #4a8f2d 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .box {
            background: #fff; border-radius: 16px; padding: 45px 40px; width: 380px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        }
        .box h1 { font-size: 22px; color: #333; margin-bottom: 4px; }
        .box p { color: #888; font-size: 14px; margin-bottom: 25px; }
        .box label { display: block; font-size: 14px; color: #555; margin-bottom: 5px; font-weight: 500; }
        .box input {
            width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 15px; margin-bottom: 18px; outline: none; transition: 0.2s;
        }
        .box input:focus { border-color: #6EBE44; }
        .box button {
            width: 100%; padding: 13px; background: #6EBE44; color: #fff; border: none;
            border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.2s;
        }
        .box button:hover { background: #5da83a; }
        .error { background: #fff0f0; color: #d32f2f; padding: 10px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; border-left: 3px solid #d32f2f; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo h1 { color: #6EBE44; font-size: 30px; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo"><h1>CAP</h1></div>
        <h1>Panel de Administración</h1>
        <p>Ingresa para gestionar el sitio web</p>
        <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
            <label for="user">Usuario</label>
            <input type="text" id="user" name="user" required autofocus>
            <label for="pass">Contraseña</label>
            <input type="password" id="pass" name="pass" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
