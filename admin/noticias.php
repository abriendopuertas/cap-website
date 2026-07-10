<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

$d = db();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Toggle active
if ($action === 'toggle' && $id) {
    $d->prepare("UPDATE news SET active = NOT active, updated_at = datetime('now') WHERE id = ?")->execute([$id]);
    flash('Estado actualizado.');
    header('Location: noticias.php');
    exit;
}

// Delete
if ($action === 'delete' && $id) {
    $d->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    flash('Noticia eliminada.');
    header('Location: noticias.php');
    exit;
}

// Delete gallery image
if ($action === 'delimg' && $id) {
    $newsId = (int)($_GET['news'] ?? 0);
    $d->prepare("DELETE FROM news_images WHERE id = ?")->execute([$id]);
    flash('Imagen eliminada.');
    header('Location: noticias.php?action=edit&id=' . $newsId);
    exit;
}

// Save (create or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = $_POST['body'] ?? '';
    $image = trim($_POST['image'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if (!$slug) $slug = slugify($title);

    // Handle main image upload
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $newName = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], UPLOADS_DIR . '/' . $newName)) {
                $image = 'uploads/' . $newName;
            }
        }
    }

    $editId = (int)($_POST['id'] ?? 0);
    if ($editId) {
        $d->prepare("UPDATE news SET title=?, slug=?, date=?, image=?, excerpt=?, body=?, active=?, updated_at=datetime('now') WHERE id=?")
          ->execute([$title, $slug, $date, $image, $excerpt, $body, $active, $editId]);
        $newsId = $editId;
        flash('Noticia actualizada.');
    } else {
        $d->prepare("INSERT INTO news (title, slug, date, image, excerpt, body, active) VALUES (?,?,?,?,?,?,?)")
          ->execute([$title, $slug, $date, $image, $excerpt, $body, $active]);
        $newsId = $d->lastInsertId();
        flash('Noticia creada.');
    }

    // Handle gallery image uploads
    if (!empty($_FILES['gallery_files']['name'][0])) {
        $maxOrder = $d->prepare("SELECT COALESCE(MAX(sort_order),0) FROM news_images WHERE news_id = ?");
        $maxOrder->execute([$newsId]);
        $order = $maxOrder->fetchColumn();

        foreach ($_FILES['gallery_files']['name'] as $i => $name) {
            if ($_FILES['gallery_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;

            $newName = 'gallery_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['gallery_files']['tmp_name'][$i], UPLOADS_DIR . '/' . $newName)) {
                $order++;
                $d->prepare("INSERT INTO news_images (news_id, filename, alt_text, sort_order) VALUES (?,?,?,?)")
                  ->execute([$newsId, 'uploads/' . $newName, pathinfo($name, PATHINFO_FILENAME), $order]);
            }
        }
    }

    // Update alt texts for existing images
    if (!empty($_POST['img_alt'])) {
        $stmtAlt = $d->prepare("UPDATE news_images SET alt_text = ? WHERE id = ? AND news_id = ?");
        foreach ($_POST['img_alt'] as $imgId => $alt) {
            $stmtAlt->execute([trim($alt), (int)$imgId, $newsId]);
        }
    }

    // Update sort order
    if (!empty($_POST['img_order'])) {
        $stmtSort = $d->prepare("UPDATE news_images SET sort_order = ? WHERE id = ? AND news_id = ?");
        foreach ($_POST['img_order'] as $imgId => $ord) {
            $stmtSort->execute([(int)$ord, (int)$imgId, $newsId]);
        }
    }

    header('Location: noticias.php?action=edit&id=' . $newsId);
    exit;
}

function slugify($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-záéíóúñü0-9\s-]/u', '', $text);
    $text = preg_replace('/[\s]+/', '-', $text);
    return trim($text, '-');
}

// === EDIT / NEW VIEW ===
if ($action === 'new' || ($action === 'edit' && $id)) {
    $news = null;
    $gallery = [];
    if ($action === 'edit') {
        $stmt = $d->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $news = $stmt->fetch();
        if (!$news) { header('Location: noticias.php'); exit; }

        $gStmt = $d->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order");
        $gStmt->execute([$id]);
        $gallery = $gStmt->fetchAll();
    }

    layoutStart($news ? 'Editar noticia' : 'Nueva noticia');
    ?>
    <h1><?= $news ? 'Editar noticia' : 'Nueva noticia' ?></h1>
    <p><a href="noticias.php">&larr; Volver a noticias</a></p>

    <form method="POST" enctype="multipart/form-data" class="card">
        <?= csrf() ?>
        <?php if ($news): ?><input type="hidden" name="id" value="<?= $news['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="title" value="<?= h($news['title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Slug (URL)</label>
                <input type="text" name="slug" value="<?= h($news['slug'] ?? '') ?>" placeholder="Se genera automáticamente">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="date" value="<?= h($news['date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:end;gap:12px;padding-bottom:18px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="active" <?= ($news['active'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px;">
                    Activa (visible en el sitio)
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>Imagen principal (card de noticias)</label>
            <?php if (!empty($news['image'])): ?>
                <div style="margin-bottom:8px;">
                    <img src="../<?= h($news['image']) ?>" style="max-width:300px;max-height:150px;border-radius:8px;object-fit:cover;">
                </div>
            <?php endif; ?>
            <input type="file" name="image_file" accept="image/*" style="margin-bottom:6px;">
            <input type="text" name="image" value="<?= h($news['image'] ?? '') ?>" placeholder="O ruta manual: uploads/imagen.jpg">
        </div>

        <div class="form-group">
            <label>Extracto (resumen para la card)</label>
            <textarea name="excerpt" rows="3"><?= h($news['excerpt'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Contenido completo (HTML permitido)</label>
            <textarea name="body" rows="12" style="font-family:monospace;font-size:13px;"><?= h($news['body'] ?? '') ?></textarea>
        </div>

        <?php if ($news): ?>
        <div style="margin:24px 0 16px;border-top:1px solid #eee;padding-top:20px;">
            <h3 style="font-size:16px;margin-bottom:12px;">Galería de imágenes (<?= count($gallery) ?>)</h3>
            <?php if ($gallery): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:16px;">
                <?php foreach ($gallery as $img): ?>
                <div style="background:#fafafa;border:1px solid #eee;border-radius:8px;padding:10px;position:relative;">
                    <img src="../<?= h($img['filename']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                    <input type="text" name="img_alt[<?= $img['id'] ?>]" value="<?= h($img['alt_text']) ?>" placeholder="Texto alt" style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;font-size:12px;margin-bottom:4px;">
                    <input type="number" name="img_order[<?= $img['id'] ?>]" value="<?= $img['sort_order'] ?>" style="width:60px;padding:4px 6px;border:1px solid #ddd;border-radius:4px;font-size:12px;" title="Orden">
                    <a href="noticias.php?action=delimg&id=<?= $img['id'] ?>&news=<?= $news['id'] ?>" onclick="return confirm('¿Eliminar imagen?')" style="position:absolute;top:6px;right:6px;background:#dc3545;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:12px;text-decoration:none;">✕</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:#aaa;font-size:14px;margin-bottom:12px;">No hay imágenes en la galería.</p>
            <?php endif; ?>

            <div class="form-group">
                <label>Agregar imágenes a la galería</label>
                <input type="file" name="gallery_files[]" accept="image/*" multiple>
                <small style="color:#888;">Puedes seleccionar varias imágenes a la vez.</small>
            </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $news ? 'Guardar cambios' : 'Crear noticia' ?></button>
            <a href="noticias.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    <?php
    layoutEnd();
    exit;
}

// === LIST VIEW ===
$news = $d->query("SELECT n.*, (SELECT COUNT(*) FROM news_images WHERE news_id = n.id) as img_count FROM news n ORDER BY date DESC, id DESC")->fetchAll();
layoutStart('Noticias');
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <div>
        <h1>Noticias</h1>
        <p style="margin-bottom:0;"><?= count($news) ?> noticias en total</p>
    </div>
    <a href="noticias.php?action=new" class="btn btn-primary">+ Nueva noticia</a>
</div>

<?php if (empty($news)): ?>
<div class="card empty">No hay noticias. Crea la primera.</div>
<?php else: ?>
<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th style="width:60px;">Img</th>
                <th>Título</th>
                <th style="width:100px;">Fecha</th>
                <th style="width:60px;">Fotos</th>
                <th style="width:80px;">Estado</th>
                <th style="width:140px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($news as $n): ?>
            <tr>
                <td>
                    <?php if ($n['image']): ?>
                    <img src="../<?= h($n['image']) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
                    <?php else: ?>
                    <div style="width:50px;height:35px;background:#eee;border-radius:4px;"></div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="font-size:14px;"><?= h($n['title']) ?></strong>
                    <?php if ($n['excerpt']): ?>
                    <br><span style="font-size:12px;color:#888;"><?= h(mb_substr($n['excerpt'], 0, 80, 'UTF-8')) ?>...</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;color:#888;"><?= $n['date'] ? date('d/m/Y', strtotime($n['date'])) : '—' ?></td>
                <td style="text-align:center;">
                    <?php if ($n['img_count'] > 0): ?>
                    <span class="badge badge-gray"><?= $n['img_count'] ?></span>
                    <?php else: ?>
                    <span style="color:#ccc;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($n['active']): ?>
                    <span class="badge badge-green">Activa</span>
                    <?php else: ?>
                    <span class="badge badge-red">Oculta</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="noticias.php?action=edit&id=<?= $n['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                    <a href="noticias.php?action=toggle&id=<?= $n['id'] ?>" class="btn btn-secondary btn-sm" title="<?= $n['active'] ? 'Ocultar' : 'Activar' ?>"><?= $n['active'] ? '👁' : '👁‍🗨' ?></a>
                    <a href="noticias.php?action=delete&id=<?= $n['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta noticia?')">✕</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php layoutEnd(); ?>
