<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

$d = db();

// Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $d->prepare("SELECT filename FROM media WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    if ($file) {
        $path = SITE_DIR . '/' . $file['filename'];
        if (file_exists($path)) @unlink($path);
        $d->prepare("DELETE FROM media WHERE id = ?")->execute([$id]);
        flash('Archivo eliminado.');
    }
    header('Location: media.php');
    exit;
}

// Update alt text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_alt') {
    checkCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $alt = trim($_POST['alt_text'] ?? '');
    $d->prepare("UPDATE media SET alt_text = ? WHERE id = ?")->execute([$alt, $id]);
    flash('Texto alt actualizado.');
    header('Location: media.php');
    exit;
}

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    checkCsrf();
    $uploaded = 0;

    if (!empty($_FILES['files']['name'][0])) {
        foreach ($_FILES['files']['name'] as $i => $name) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg'])) continue;

            $size = $_FILES['files']['size'][$i];
            if ($size > 10 * 1024 * 1024) continue; // 10MB max

            $newName = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $dest = UPLOADS_DIR . '/' . $newName;

            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
                $d->prepare("INSERT INTO media (filename, original_name, alt_text) VALUES (?, ?, ?)")
                  ->execute(['uploads/' . $newName, $name, pathinfo($name, PATHINFO_FILENAME)]);
                $uploaded++;
            }
        }
    }

    flash($uploaded . ' archivo(s) subido(s).');
    header('Location: media.php');
    exit;
}

// Import existing _wix_assets into media library
if (isset($_GET['action']) && $_GET['action'] === 'import_wix') {
    $imported = 0;
    $wixDir = SITE_DIR . '/_wix_assets';
    if (is_dir($wixDir)) {
        foreach (glob($wixDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) as $file) {
            $basename = basename($file);
            $relPath = '_wix_assets/' . $basename;
            $exists = $d->prepare("SELECT id FROM media WHERE filename = ?");
            $exists->execute([$relPath]);
            if (!$exists->fetch()) {
                $d->prepare("INSERT INTO media (filename, original_name, alt_text) VALUES (?, ?, ?)")
                  ->execute([$relPath, $basename, '']);
                $imported++;
            }
        }
    }
    flash($imported . ' imágenes importadas desde _wix_assets.');
    header('Location: media.php');
    exit;
}

// Get all media
$media = $d->query("SELECT * FROM media ORDER BY uploaded_at DESC, id DESC")->fetchAll();
$totalSize = 0;
foreach ($media as $m) {
    $path = SITE_DIR . '/' . $m['filename'];
    if (file_exists($path)) $totalSize += filesize($path);
}

layoutStart('Media');
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <div>
        <h1>Media</h1>
        <p style="margin-bottom:0;"><?= count($media) ?> archivos · <?= round($totalSize / 1024 / 1024, 1) ?> MB total</p>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if (is_dir(SITE_DIR . '/_wix_assets')): ?>
        <a href="media.php?action=import_wix" class="btn btn-secondary" onclick="return confirm('¿Importar imágenes de _wix_assets?')">Importar Wix assets</a>
        <?php endif; ?>
    </div>
</div>

<!-- Upload form -->
<div class="card" style="margin-bottom:20px;">
    <h3 style="font-size:15px;margin-bottom:12px;">Subir imágenes</h3>
    <form method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;">
        <?= csrf() ?>
        <input type="hidden" name="action" value="upload">
        <div style="flex:1;">
            <input type="file" name="files[]" accept="image/*" multiple style="font-size:14px;">
            <small style="color:#888;display:block;margin-top:4px;">JPG, PNG, WebP, GIF, SVG. Máx 10MB por archivo.</small>
        </div>
        <button type="submit" class="btn btn-primary">Subir</button>
    </form>
</div>

<!-- Media grid -->
<?php if (empty($media)): ?>
<div class="card empty">No hay archivos. Sube imágenes o importa desde _wix_assets.</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
    <?php foreach ($media as $m):
        $path = SITE_DIR . '/' . $m['filename'];
        $fileSize = file_exists($path) ? round(filesize($path) / 1024) : 0;
    ?>
    <div class="card" style="padding:10px;position:relative;">
        <img src="../<?= h($m['filename']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;background:#f0f0f0;">

        <!-- Copy path button -->
        <div style="font-size:11px;color:#888;word-break:break-all;margin-bottom:6px;cursor:pointer;background:#f8f8f8;padding:4px 6px;border-radius:4px;"
             onclick="navigator.clipboard.writeText('<?= h($m['filename']) ?>');this.style.background='#e8f5e1';setTimeout(()=>this.style.background='#f8f8f8',1000);"
             title="Clic para copiar ruta">
            <?= h($m['filename']) ?>
        </div>

        <div style="font-size:11px;color:#aaa;margin-bottom:6px;"><?= $fileSize ?> KB</div>

        <!-- Alt text form -->
        <form method="POST" style="display:flex;gap:4px;margin-bottom:6px;">
            <?= csrf() ?>
            <input type="hidden" name="action" value="update_alt">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <input type="text" name="alt_text" value="<?= h($m['alt_text']) ?>" placeholder="Texto alt"
                   style="flex:1;padding:4px 6px;border:1px solid #ddd;border-radius:4px;font-size:11px;">
            <button type="submit" style="padding:4px 8px;background:#eee;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;">✓</button>
        </form>

        <a href="media.php?action=delete&id=<?= $m['id'] ?>" onclick="return confirm('¿Eliminar este archivo?')"
           style="position:absolute;top:6px;right:6px;background:rgba(220,53,69,0.9);color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:12px;text-decoration:none;">✕</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php layoutEnd(); ?>
