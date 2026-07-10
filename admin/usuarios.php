<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

$d = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['user_action'] ?? '';

    // Create user
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$name || !$password) {
            flash('Todos los campos son obligatorios.', 'error');
        } elseif (strlen($password) < 6) {
            flash('La contraseña debe tener al menos 6 caracteres.', 'error');
        } else {
            $exists = $d->prepare("SELECT id FROM users WHERE username = ?");
            $exists->execute([$username]);
            if ($exists->fetch()) {
                flash('El nombre de usuario ya existe.', 'error');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $d->prepare("INSERT INTO users (username, password_hash, name) VALUES (?, ?, ?)")
                  ->execute([$username, $hash, $name]);
                flash('Usuario "' . $username . '" creado.');
            }
        }
        header('Location: usuarios.php');
        exit;
    }

    // Change password
    if ($action === 'change_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if (!$userId || !$password) {
            flash('Contraseña requerida.', 'error');
        } elseif (strlen($password) < 6) {
            flash('La contraseña debe tener al menos 6 caracteres.', 'error');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $d->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
              ->execute([$hash, $userId]);
            flash('Contraseña actualizada.');
        }
        header('Location: usuarios.php');
        exit;
    }

    // Update name
    if ($action === 'update_name') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($userId && $name) {
            $d->prepare("UPDATE users SET name = ? WHERE id = ?")
              ->execute([$name, $userId]);
            flash('Nombre actualizado.');
        }
        header('Location: usuarios.php');
        exit;
    }

    // Delete user
    if ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $total = $d->query("SELECT COUNT(*) FROM users")->fetchColumn();

        if ($total <= 1) {
            flash('No se puede eliminar el único usuario.', 'error');
        } elseif ($userId == ($_SESSION['admin_user']['id'] ?? 0)) {
            flash('No puedes eliminarte a ti mismo.', 'error');
        } else {
            $d->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            flash('Usuario eliminado.');
        }
        header('Location: usuarios.php');
        exit;
    }
}

$users = $d->query("SELECT id, username, name FROM users ORDER BY id")->fetchAll();

layoutStart('Usuarios');
?>
<h1>Usuarios</h1>
<p>Administra quién tiene acceso al panel.</p>

<!-- Existing users -->
<?php foreach ($users as $u): ?>
<div class="card" style="margin-bottom:12px;">
    <div style="display:flex;gap:16px;align-items:center;">
        <div style="flex:0 0 48px;height:48px;background:#6EBE44;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">
            <?= strtoupper(mb_substr($u['name'], 0, 1)) ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:16px;font-weight:600;"><?= h($u['name']) ?></div>
            <div style="font-size:13px;color:#888;">@<?= h($u['username']) ?></div>
        </div>
        <?php if ($u['id'] == ($_SESSION['admin_user']['id'] ?? 0)): ?>
        <span style="font-size:12px;color:#6EBE44;font-weight:600;background:#e8f5e9;padding:4px 10px;border-radius:12px;">Tú</span>
        <?php endif; ?>
    </div>

    <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;">
        <!-- Update name -->
        <form method="POST" style="display:flex;gap:6px;align-items:center;">
            <?= csrf() ?>
            <input type="hidden" name="user_action" value="update_name">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="text" name="name" value="<?= h($u['name']) ?>" placeholder="Nombre"
                   style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;width:180px;">
            <button type="submit" class="btn btn-primary btn-sm" style="font-size:12px;">Cambiar nombre</button>
        </form>

        <!-- Change password -->
        <form method="POST" style="display:flex;gap:6px;align-items:center;">
            <?= csrf() ?>
            <input type="hidden" name="user_action" value="change_password">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="password" name="password" placeholder="Nueva contraseña" minlength="6"
                   style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;width:160px;">
            <button type="submit" class="btn btn-primary btn-sm" style="font-size:12px;">Cambiar clave</button>
        </form>

        <!-- Delete -->
        <?php if (count($users) > 1 && $u['id'] != ($_SESSION['admin_user']['id'] ?? 0)): ?>
        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar a <?= h(addslashes($u['name'])) ?>?');">
            <?= csrf() ?>
            <input type="hidden" name="user_action" value="delete">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button type="submit" style="padding:6px 14px;background:#fde8e8;color:#dc3545;border:1px solid #f5c6c6;border-radius:6px;cursor:pointer;font-size:12px;">Eliminar</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Create new user -->
<div class="card" style="margin-top:20px;background:#fafffe;border:1px dashed #6EBE44;">
    <h3 style="font-size:16px;margin-bottom:16px;">Crear nuevo usuario</h3>
    <form method="POST">
        <?= csrf() ?>
        <input type="hidden" name="user_action" value="create">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" placeholder="Ej: María García" required>
            </div>
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" placeholder="Ej: maria" required pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guión bajo">
            </div>
        </div>
        <div class="form-group" style="max-width:300px;">
            <label>Contraseña</label>
            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary">Crear usuario</button>
    </form>
</div>
<?php layoutEnd(); ?>
