<?php $titulo = 'Páginas'; require_once __DIR__ . '/_header.php';
$config = dataGet('config');

// Toggle
if (isset($_GET['t'])) {
    $n = $_GET['t'];
    if (isset($config['menu'][$n])) {
        $config['menu'][$n]['activa'] = !($config['menu'][$n]['activa'] ?? true);
        dataSave('config', $config);
        flash('Estado actualizado.');
    }
    header('Location: paginas.php'); exit;
}

// Create new page from template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    checkCsrf();
    $titulo = trim($_POST['titulo'] ?? '');
    $archivo = trim($_POST['archivo'] ?? '');
    if ($titulo && $archivo) {
        $config['menu'][$titulo] = ['archivo' => $archivo, 'activa' => true];
        dataSave('config', $config);
        // Copy base template
        $base = PLANTILLAS_DIR . '/_base.html';
        if (file_exists($base)) {
            copy($base, PLANTILLAS_DIR . '/' . $archivo);
        }
        flash('✅ Página creada. Agrega los marcadores {{...}} en la plantilla.');
    }
    header('Location: paginas.php'); exit;
}
?>
<h1>📄 Páginas</h1>
<p style="color:#888;margin-bottom:15px;">Activa o desactiva páginas del menú principal.</p>

<div class="lista">
<?php foreach ($config['menu'] ?? [] as $nom => $p): ?>
    <div class="item <?= ($p['activa']??true)?'':'off' ?>">
        <div class="info">
            <strong><?= h($nom) ?></strong>
            <span class="file"><?= h($p['archivo']) ?></span>
        </div>
        <?php $tpl = PLANTILLAS_DIR . '/' . $p['archivo']; ?>
        <span class="badge <?= file_exists($tpl)?'ok':'no' ?>"><?= file_exists($tpl)?'✅ Plantilla':'📄 Sin plantilla' ?></span>
        <a href="?t=<?= urlencode($nom) ?>" class="btn-sm"><?= ($p['activa']??true)?'🔴 Ocultar':'🟢 Mostrar' ?></a>
    </div>
<?php endforeach; ?>
</div>

<details style="margin-top:25px;background:#fff;border-radius:10px;padding:20px;border:1px solid #eee;">
    <summary style="cursor:pointer;font-weight:600;color:#6EBE44;">➕ Crear nueva página</summary>
    <form method="post" style="margin-top:15px;">
        <?= csrf() ?><input type="hidden" name="crear" value="1">
        <div class="row">
            <div class="field"><label>Nombre página</label><input type="text" name="titulo" placeholder="Ej: Galería" required></div>
            <div class="field"><label>Archivo</label><input type="text" name="archivo" placeholder="Ej: galeria.html" required></div>
        </div>
        <button type="submit" class="btn">Crear página</button>
    </form>
</details>

<style>
.lista { display:flex; flex-direction:column; gap:8px; }
.item { display:flex; align-items:center; gap:12px; background:#fff; padding:12px 15px; border-radius:10px; border:1px solid #eee; }
.item.off { opacity:0.5; }
.info { flex:1; min-width:0; }
.info strong { display:block; font-size:14px; }
.info .file { font-size:12px; color:#aaa; font-family:monospace; }
.badge { font-size:12px; padding:3px 10px; border-radius:20px; }
.badge.ok { background:#e8f5e9; color:#2e7d32; }
.badge.no { background:#fff3e0; color:#e65100; }
.btn-sm { padding:5px 12px; border-radius:6px; text-decoration:none; font-size:12px; background:#f5f5f5; color:#666; }
.btn-sm:hover { background:#eee; }
.btn { background:#6EBE44; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
.row { display:flex; gap:15px; } .row .field { flex:1; }
.field { margin-bottom:14px; }
.field label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:4px; }
.field input { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; box-sizing:border-box; }
.field input:focus { border-color:#6EBE44; }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
