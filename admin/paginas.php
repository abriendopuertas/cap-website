<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

$d = db();

$pageNames = [
    'index' => 'Inicio',
    'nosotros' => 'Nosotros',
    'quehacemos' => 'Qué Hacemos',
    'hazteparte' => 'Hazte Parte',
    'otec' => 'OTEC Promueve',
    'contacto' => 'Contacto',
    'footer' => 'Footer (global)',
];

$currentPage = $_GET['p'] ?? 'index';
if (!isset($pageNames[$currentPage])) $currentPage = 'index';

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $page = $_POST['page'] ?? 'index';

    // Handle team member updates (name, role, photo)
    if (!empty($_POST['member_action']) && $_POST['member_action'] === 'update_members') {
        $updated = 0;
        foreach ($_POST['member_name'] ?? [] as $memberId => $name) {
            $role = $_POST['member_role'][$memberId] ?? '';
            $d->prepare("UPDATE team_members SET name = ?, role = ? WHERE id = ?")
              ->execute([trim($name), trim($role), $memberId]);
            $updated++;
        }
        foreach ($_FILES['member_photo']['name'] ?? [] as $memberId => $filename) {
            if ($_FILES['member_photo']['error'][$memberId] !== UPLOAD_ERR_OK) continue;
            if (empty($filename)) continue;

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;

            $newName = 'team_' . $memberId . '_' . time() . '.' . $ext;
            $dest = UPLOADS_DIR . '/' . $newName;

            if (move_uploaded_file($_FILES['member_photo']['tmp_name'][$memberId], $dest)) {
                $imgPath = 'uploads/' . $newName;
                $d->prepare("UPDATE team_members SET image = ? WHERE id = ?")->execute([$imgPath, $memberId]);
            }
        }
        flash($updated . ' integrante(s) actualizado(s).');
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Add new team member
    if (!empty($_POST['member_action']) && $_POST['member_action'] === 'add_member') {
        $name = trim($_POST['new_name'] ?? '');
        $role = trim($_POST['new_role'] ?? '');
        $section = $_POST['new_section'] ?? 'directorio';
        if (!in_array($section, ['directorio', 'equipo'])) $section = 'directorio';

        if ($name) {
            $maxOrder = $d->prepare("SELECT MAX(sort_order) FROM team_members WHERE section = ?");
            $maxOrder->execute([$section]);
            $nextOrder = ((int)$maxOrder->fetchColumn()) + 1;

            $imgPath = '';
            if (!empty($_FILES['new_photo']['name']) && $_FILES['new_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['new_photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $newName = 'team_new_' . time() . '.' . $ext;
                    $dest = UPLOADS_DIR . '/' . $newName;
                    if (move_uploaded_file($_FILES['new_photo']['tmp_name'], $dest)) {
                        $imgPath = 'uploads/' . $newName;
                    }
                }
            }

            $d->prepare("INSERT INTO team_members (section, name, role, image, original_image, sort_order) VALUES (?, ?, ?, ?, ?, ?)")
              ->execute([$section, $name, $role, $imgPath, $imgPath, $nextOrder]);
            flash('Integrante "' . $name . '" agregado.');
        }
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Move team member up/down
    if (!empty($_POST['member_action']) && $_POST['member_action'] === 'move_member') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        $dir = $_POST['direction'] ?? '';
        if ($memberId && in_array($dir, ['up', 'down'])) {
            $cur = $d->prepare("SELECT id, section, sort_order FROM team_members WHERE id = ?");
            $cur->execute([$memberId]);
            $cur = $cur->fetch();
            if ($cur) {
                $op = $dir === 'up' ? '<' : '>';
                $ord = $dir === 'up' ? 'DESC' : 'ASC';
                $neighbor = $d->prepare("SELECT id, sort_order FROM team_members WHERE section = ? AND sort_order {$op} ? ORDER BY sort_order {$ord} LIMIT 1");
                $neighbor->execute([$cur['section'], $cur['sort_order']]);
                $neighbor = $neighbor->fetch();
                if ($neighbor) {
                    $d->prepare("UPDATE team_members SET sort_order = ? WHERE id = ?")->execute([$neighbor['sort_order'], $cur['id']]);
                    $d->prepare("UPDATE team_members SET sort_order = ? WHERE id = ?")->execute([$cur['sort_order'], $neighbor['id']]);
                }
            }
        }
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Delete team member
    if (!empty($_POST['member_action']) && $_POST['member_action'] === 'delete_member') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId) {
            $d->prepare("DELETE FROM team_members WHERE id = ?")->execute([$memberId]);
            flash('Integrante eliminado.');
        }
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Update downloads (title edits)
    if (!empty($_POST['dl_action']) && $_POST['dl_action'] === 'update_downloads') {
        $updated = 0;
        foreach ($_POST['dl_title'] ?? [] as $dlId => $title) {
            $d->prepare("UPDATE downloads SET title = ? WHERE id = ?")
              ->execute([trim($title), $dlId]);
            $updated++;
        }
        flash($updated . ' descargable(s) actualizado(s).');
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Add download
    if (!empty($_POST['dl_action']) && $_POST['dl_action'] === 'add_download') {
        $title = trim($_POST['dl_title_new'] ?? '');
        $section = $_POST['dl_section'] ?? 'memorias';
        if ($title && !empty($_FILES['dl_file']['name']) && $_FILES['dl_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['dl_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip'])) {
                $safeName = 'dl_' . time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $_FILES['dl_file']['name']);
                $dest = UPLOADS_DIR . '/' . $safeName;
                if (move_uploaded_file($_FILES['dl_file']['tmp_name'], $dest)) {
                    $maxOrder = $d->prepare("SELECT MAX(sort_order) FROM downloads WHERE section = ?");
                    $maxOrder->execute([$section]);
                    $next = ((int)$maxOrder->fetchColumn()) + 1;
                    $d->prepare("INSERT INTO downloads (section, title, file_path, sort_order) VALUES (?, ?, ?, ?)")
                      ->execute([$section, $title, 'uploads/' . $safeName, $next]);
                    flash('Descargable "' . $title . '" agregado.');
                }
            }
        }
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Move download up/down
    if (!empty($_POST['dl_action']) && $_POST['dl_action'] === 'move_download') {
        $dlId = (int)($_POST['dl_id'] ?? 0);
        $dir = $_POST['direction'] ?? '';
        if ($dlId && in_array($dir, ['up', 'down'])) {
            $cur = $d->prepare("SELECT id, section, sort_order FROM downloads WHERE id = ?");
            $cur->execute([$dlId]);
            $cur = $cur->fetch();
            if ($cur) {
                $op = $dir === 'up' ? '<' : '>';
                $ord = $dir === 'up' ? 'DESC' : 'ASC';
                $neighbor = $d->prepare("SELECT id, sort_order FROM downloads WHERE section = ? AND sort_order {$op} ? ORDER BY sort_order {$ord} LIMIT 1");
                $neighbor->execute([$cur['section'], $cur['sort_order']]);
                $neighbor = $neighbor->fetch();
                if ($neighbor) {
                    $d->prepare("UPDATE downloads SET sort_order = ? WHERE id = ?")->execute([$neighbor['sort_order'], $cur['id']]);
                    $d->prepare("UPDATE downloads SET sort_order = ? WHERE id = ?")->execute([$cur['sort_order'], $neighbor['id']]);
                }
            }
        }
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Delete download
    if (!empty($_POST['dl_action']) && $_POST['dl_action'] === 'delete_download') {
        $dlId = (int)($_POST['dl_id'] ?? 0);
        if ($dlId) {
            $d->prepare("DELETE FROM downloads WHERE id = ?")->execute([$dlId]);
            flash('Descargable eliminado.');
        }
        header('Location: paginas.php?p=nosotros');
        exit;
    }

    // Add stat
    if (!empty($_POST['stat_action']) && $_POST['stat_action'] === 'add_stat') {
        $statPage = $_POST['stat_page'] ?? '';
        $statGroup = $_POST['stat_group'] ?? 'main';
        $number = trim($_POST['stat_number'] ?? '');
        $text = trim($_POST['stat_text'] ?? '');
        if ($text) {
            $maxOrder = $d->prepare("SELECT MAX(sort_order) FROM page_stats WHERE page = ? AND stat_group = ?");
            $maxOrder->execute([$statPage, $statGroup]);
            $next = ((int)$maxOrder->fetchColumn()) + 1;
            $d->prepare("INSERT INTO page_stats (page, stat_group, number, text, sort_order) VALUES (?, ?, ?, ?, ?)")
              ->execute([$statPage, $statGroup, $number, $text, $next]);
            flash('Estadística agregada.');
        }
        header('Location: paginas.php?p=' . urlencode($page));
        exit;
    }

    // Update stats
    if (!empty($_POST['stat_action']) && $_POST['stat_action'] === 'update_stats') {
        $updated = 0;
        foreach ($_POST['stat_number'] ?? [] as $statId => $number) {
            $text = $_POST['stat_text'][$statId] ?? '';
            $d->prepare("UPDATE page_stats SET number = ?, text = ? WHERE id = ?")
              ->execute([trim($number), trim($text), $statId]);
            $updated++;
        }
        flash($updated . ' estadística(s) actualizada(s).');
        header('Location: paginas.php?p=' . urlencode($page));
        exit;
    }

    // Move stat up/down
    if (!empty($_POST['stat_action']) && $_POST['stat_action'] === 'move_stat') {
        $statId = (int)($_POST['stat_id'] ?? 0);
        $dir = $_POST['direction'] ?? '';
        if ($statId && in_array($dir, ['up', 'down'])) {
            $cur = $d->prepare("SELECT id, page, stat_group, sort_order FROM page_stats WHERE id = ?");
            $cur->execute([$statId]);
            $cur = $cur->fetch();
            if ($cur) {
                $op = $dir === 'up' ? '<' : '>';
                $ord = $dir === 'up' ? 'DESC' : 'ASC';
                $neighbor = $d->prepare("SELECT id, sort_order FROM page_stats WHERE page = ? AND stat_group = ? AND sort_order {$op} ? ORDER BY sort_order {$ord} LIMIT 1");
                $neighbor->execute([$cur['page'], $cur['stat_group'], $cur['sort_order']]);
                $neighbor = $neighbor->fetch();
                if ($neighbor) {
                    $d->prepare("UPDATE page_stats SET sort_order = ? WHERE id = ?")->execute([$neighbor['sort_order'], $cur['id']]);
                    $d->prepare("UPDATE page_stats SET sort_order = ? WHERE id = ?")->execute([$cur['sort_order'], $neighbor['id']]);
                }
            }
        }
        header('Location: paginas.php?p=' . urlencode($page));
        exit;
    }

    // Delete stat
    if (!empty($_POST['stat_action']) && $_POST['stat_action'] === 'delete_stat') {
        $statId = (int)($_POST['stat_id'] ?? 0);
        if ($statId) {
            $d->prepare("DELETE FROM page_stats WHERE id = ?")->execute([$statId]);
            flash('Estadística eliminada.');
        }
        header('Location: paginas.php?p=' . urlencode($page));
        exit;
    }

    foreach ($_POST['fields'] ?? [] as $field => $value) {
        $d->prepare("UPDATE page_content SET value = ? WHERE page = ? AND field = ?")
          ->execute([$value, $page, $field]);
    }

    flash('Contenido de "' . ($pageNames[$page] ?? $page) . '" guardado.');
    header('Location: paginas.php?p=' . urlencode($page));
    exit;
}

// Get fields for current page
$stmt = $d->prepare("SELECT * FROM page_content WHERE page = ? ORDER BY sort_order");
$stmt->execute([$currentPage]);
$fields = $stmt->fetchAll();

layoutStart('Editar: ' . $pageNames[$currentPage]);
?>
<h1>Editar Páginas</h1>
<p>Contenido editable por sección del sitio.</p>

<div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
    <?php foreach ($pageNames as $key => $name): ?>
    <a href="paginas.php?p=<?= $key ?>"
       class="btn <?= $currentPage === $key ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        <?= h($name) ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" class="card">
    <?= csrf() ?>
    <input type="hidden" name="page" value="<?= h($currentPage) ?>">

    <h2 style="font-size:18px;margin-bottom:20px;"><?= h($pageNames[$currentPage]) ?></h2>

    <?php foreach ($fields as $f): ?>
    <div class="form-group">
        <label><?= h($f['label'] ?: $f['field']) ?></label>
        <?php if ($f['field_type'] === 'textarea'): ?>
        <textarea name="fields[<?= h($f['field']) ?>]" rows="4"><?= h($f['value']) ?></textarea>
        <?php elseif ($f['field_type'] === 'image'): ?>
        <?php if ($f['value']): ?>
        <div style="margin-bottom:6px;">
            <img src="../<?= h($f['value']) ?>" style="max-width:200px;max-height:100px;border-radius:6px;object-fit:cover;">
        </div>
        <?php endif; ?>
        <input type="text" name="fields[<?= h($f['field']) ?>]" value="<?= h($f['value']) ?>" placeholder="Ruta de imagen">
        <?php else: ?>
        <input type="text" name="fields[<?= h($f['field']) ?>]" value="<?= h($f['value']) ?>">
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($fields)): ?>
    <div class="empty">No hay campos configurados para esta página.</div>
    <?php else: ?>
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
    <?php endif; ?>
</form>

<?php
// Dynamic stats sections
$statGroups = [];
$stmtStat = $d->prepare("SELECT DISTINCT stat_group FROM page_stats WHERE page = ? ORDER BY stat_group");
$stmtStat->execute([$currentPage]);
$statGroups = $stmtStat->fetchAll(PDO::FETCH_COLUMN);

$groupLabels = [
    'main' => 'Estadísticas',
    'intra' => 'Estadísticas — Programa Intra',
    'libertad' => 'Estadísticas — Programa Libertad',
];

if (!empty($statGroups)):
foreach ($statGroups as $sg):
    $stats = $d->prepare("SELECT * FROM page_stats WHERE page = ? AND stat_group = ? ORDER BY sort_order");
    $stats->execute([$currentPage, $sg]);
    $stats = $stats->fetchAll();
    $groupLabel = $groupLabels[$sg] ?? 'Estadísticas — ' . ucfirst($sg);
?>

<!-- Stats: <?= h($sg) ?> -->
<form method="POST" class="card" style="margin-top:20px;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="<?= h($currentPage) ?>">
    <input type="hidden" name="stat_action" value="update_stats">

    <h2 style="font-size:18px;margin-bottom:16px;"><?= h($groupLabel) ?></h2>

    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
        <?php foreach ($stats as $s): ?>
        <div style="display:flex;gap:12px;align-items:center;padding:12px;border:1px solid #eee;border-radius:8px;">
            <div style="flex:0 0 28px;display:flex;flex-direction:column;gap:2px;">
                <button type="button" onclick="document.getElementById('move-stat-<?= $s['id'] ?>-up').submit();"
                        style="width:28px;height:24px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;line-height:1;" title="Subir">&#9650;</button>
                <button type="button" onclick="document.getElementById('move-stat-<?= $s['id'] ?>-down').submit();"
                        style="width:28px;height:24px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;line-height:1;" title="Bajar">&#9660;</button>
            </div>
            <div style="flex:0 0 120px;">
                <label style="font-size:11px;color:#888;display:block;margin-bottom:3px;">Número</label>
                <input type="text" name="stat_number[<?= $s['id'] ?>]" value="<?= h($s['number']) ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:20px;font-weight:700;color:#6EBE44;text-align:center;">
            </div>
            <div style="flex:1;">
                <label style="font-size:11px;color:#888;display:block;margin-bottom:3px;">Texto</label>
                <input type="text" name="stat_text[<?= $s['id'] ?>]" value="<?= h($s['text']) ?>"
                       style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                <span style="font-size:10px;color:#999;">Usa *palabra* para resaltar en verde</span>
            </div>
            <button type="button" onclick="if(confirm('¿Eliminar esta estadística?')){document.getElementById('del-stat-<?= $s['id'] ?>').submit();}"
                    style="flex:0 0 32px;height:32px;background:#fde8e8;color:#dc3545;border:1px solid #f5c6c6;border-radius:6px;cursor:pointer;font-size:14px;">&times;</button>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary btn-sm">Guardar estadísticas</button>
    </div>
</form>

<?php foreach ($stats as $s): ?>
<form id="del-stat-<?= $s['id'] ?>" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="<?= h($currentPage) ?>">
    <input type="hidden" name="stat_action" value="delete_stat">
    <input type="hidden" name="stat_id" value="<?= $s['id'] ?>">
</form>
<form id="move-stat-<?= $s['id'] ?>-up" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="<?= h($currentPage) ?>">
    <input type="hidden" name="stat_action" value="move_stat">
    <input type="hidden" name="stat_id" value="<?= $s['id'] ?>">
    <input type="hidden" name="direction" value="up">
</form>
<form id="move-stat-<?= $s['id'] ?>-down" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="<?= h($currentPage) ?>">
    <input type="hidden" name="stat_action" value="move_stat">
    <input type="hidden" name="stat_id" value="<?= $s['id'] ?>">
    <input type="hidden" name="direction" value="down">
</form>
<?php endforeach; ?>

<!-- Add stat -->
<form method="POST" class="card" style="margin-top:8px;background:#fafffe;border:1px dashed #6EBE44;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="<?= h($currentPage) ?>">
    <input type="hidden" name="stat_action" value="add_stat">
    <input type="hidden" name="stat_page" value="<?= h($currentPage) ?>">
    <input type="hidden" name="stat_group" value="<?= h($sg) ?>">

    <h3 style="font-size:14px;margin-bottom:10px;">Agregar estadística</h3>
    <div style="display:flex;gap:12px;align-items:end;">
        <div style="flex:0 0 120px;">
            <label style="font-size:11px;color:#888;display:block;margin-bottom:3px;">Número</label>
            <input type="text" name="stat_number" placeholder="Ej: +50% (vacío = solo texto)"
                   style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:16px;font-weight:700;text-align:center;">
        </div>
        <div style="flex:1;">
            <label style="font-size:11px;color:#888;display:block;margin-bottom:3px;">Texto</label>
            <input type="text" name="stat_text" required placeholder="Usa *palabra* para resaltar en verde"
                   style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="flex:0 0 auto;">Agregar</button>
    </div>
</form>

<?php endforeach; endif; ?>

<?php if ($currentPage === 'nosotros'):
    $sections = ['directorio' => 'Directorio', 'equipo' => 'Equipo de Trabajo'];
    foreach ($sections as $secKey => $secTitle):
        $members = $d->prepare("SELECT * FROM team_members WHERE section = ? ORDER BY sort_order");
        $members->execute([$secKey]);
        $members = $members->fetchAll();
?>
<form method="POST" enctype="multipart/form-data" class="card" style="margin-top:20px;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="member_action" value="update_members">

    <h2 style="font-size:18px;margin-bottom:16px;"><?= h($secTitle) ?></h2>

    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
        <?php foreach ($members as $m): ?>
        <div style="display:flex;gap:14px;align-items:center;padding:12px;border:1px solid #eee;border-radius:10px;">
            <div style="flex:0 0 28px;display:flex;flex-direction:column;gap:2px;">
                <button type="button" onclick="document.getElementById('move-member-<?= $m['id'] ?>-up').submit();"
                        style="width:28px;height:24px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;line-height:1;" title="Subir">&#9650;</button>
                <button type="button" onclick="document.getElementById('move-member-<?= $m['id'] ?>-down').submit();"
                        style="width:28px;height:24px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;line-height:1;" title="Bajar">&#9660;</button>
            </div>
            <div style="flex:0 0 80px;text-align:center;">
                <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:#f0f0f0;margin-bottom:6px;">
                    <?php if ($m['image']): ?>
                    <img src="../<?= h($m['image']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:28px;">?</div>
                    <?php endif; ?>
                </div>
                <label style="display:inline-block;padding:3px 8px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;">
                    Cambiar
                    <input type="file" name="member_photo[<?= $m['id'] ?>]" accept="image/*" style="display:none;"
                           onchange="this.closest('label').style.background='#e8f5e9';this.closest('label').style.borderColor='#6EBE44';this.closest('label').childNodes[0].textContent='Listo ';">
                </label>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                <div>
                    <label style="font-size:11px;color:#888;">Nombre</label>
                    <input type="text" name="member_name[<?= $m['id'] ?>]" value="<?= h($m['name']) ?>"
                           style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:600;">
                </div>
                <div>
                    <label style="font-size:11px;color:#888;">Cargo</label>
                    <input type="text" name="member_role[<?= $m['id'] ?>]" value="<?= h($m['role']) ?>"
                           style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;color:#555;">
                </div>
            </div>
            <button type="button" onclick="if(confirm('¿Eliminar a <?= h(addslashes($m['name'])) ?>?')){document.getElementById('del-<?= $m['id'] ?>').submit();}"
                    style="flex:0 0 32px;height:32px;background:#fde8e8;color:#dc3545;border:1px solid #f5c6c6;border-radius:6px;cursor:pointer;font-size:14px;align-self:flex-start;">&times;</button>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>

<?php foreach ($members as $m): ?>
<form id="del-<?= $m['id'] ?>" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="member_action" value="delete_member">
    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
</form>
<form id="move-member-<?= $m['id'] ?>-up" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="member_action" value="move_member">
    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
    <input type="hidden" name="direction" value="up">
</form>
<form id="move-member-<?= $m['id'] ?>-down" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="member_action" value="move_member">
    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
    <input type="hidden" name="direction" value="down">
</form>
<?php endforeach; ?>

<!-- Add new member form -->
<form method="POST" enctype="multipart/form-data" class="card" style="margin-top:12px;background:#fafffe;border:1px dashed #6EBE44;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="member_action" value="add_member">
    <input type="hidden" name="new_section" value="<?= h($secKey) ?>">

    <h3 style="font-size:15px;margin-bottom:14px;">Agregar integrante a <?= h($secTitle) ?></h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div class="form-group" style="margin-bottom:0;">
            <label>Nombre</label>
            <input type="text" name="new_name" required placeholder="Nombre completo">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>Cargo / Rol</label>
            <input type="text" name="new_role" placeholder="Ej: Directora Ejecutiva">
        </div>
    </div>
    <div class="form-group" style="margin-bottom:14px;">
        <label>Foto</label>
        <input type="file" name="new_photo" accept="image/*" style="font-size:13px;">
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Agregar integrante</button>
</form>
<?php endforeach; endif; ?>

<?php if ($currentPage === 'nosotros'):
    $downloads = $d->prepare("SELECT * FROM downloads WHERE section = 'memorias' ORDER BY sort_order");
    $downloads->execute();
    $downloads = $downloads->fetchAll();
?>
<!-- Downloads / Descargables -->
<div class="card" style="margin-top:20px;">
    <h2 style="font-size:18px;margin-bottom:16px;">Descargables — Memoria & Balance</h2>

    <form method="POST" class="card" style="margin-top:0;border:none;padding:0;">
        <?= csrf() ?>
        <input type="hidden" name="page" value="nosotros">
        <input type="hidden" name="dl_action" value="update_downloads">

        <div style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($downloads as $dl): ?>
            <div style="display:flex;gap:10px;align-items:center;padding:10px;border:1px solid #eee;border-radius:8px;">
                <div style="flex:0 0 28px;display:flex;flex-direction:column;gap:2px;">
                    <button type="button" onclick="document.getElementById('move-dl-<?= $dl['id'] ?>-up').submit();"
                            style="width:28px;height:24px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;line-height:1;" title="Subir">&#9650;</button>
                    <button type="button" onclick="document.getElementById('move-dl-<?= $dl['id'] ?>-down').submit();"
                            style="width:28px;height:24px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:12px;line-height:1;" title="Bajar">&#9660;</button>
                </div>
                <div style="flex:0 0 30px;text-align:center;font-size:20px;color:#dc3545;">&#128196;</div>
                <div style="flex:1;">
                    <input type="text" name="dl_title[<?= $dl['id'] ?>]" value="<?= h($dl['title']) ?>"
                           style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                    <div style="font-size:11px;color:#999;margin-top:2px;"><?= h($dl['file_path']) ?></div>
                </div>
                <button type="button" onclick="if(confirm('¿Eliminar este descargable?')){document.getElementById('del-dl-<?= $dl['id'] ?>').submit();}"
                        style="flex:0 0 32px;height:32px;background:#fde8e8;color:#dc3545;border:1px solid #f5c6c6;border-radius:6px;cursor:pointer;font-size:14px;">&times;</button>
            </div>
        <?php endforeach; ?>
        </div>

        <?php if ($downloads): ?>
        <button type="submit" class="btn btn-primary" style="margin-top:12px;">Guardar cambios</button>
        <?php endif; ?>
    </form>
</div>

<?php foreach ($downloads as $dl): ?>
<form id="del-dl-<?= $dl['id'] ?>" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="dl_action" value="delete_download">
    <input type="hidden" name="dl_id" value="<?= $dl['id'] ?>">
</form>
<form id="move-dl-<?= $dl['id'] ?>-up" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="dl_action" value="move_download">
    <input type="hidden" name="dl_id" value="<?= $dl['id'] ?>">
    <input type="hidden" name="direction" value="up">
</form>
<form id="move-dl-<?= $dl['id'] ?>-down" method="POST" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="dl_action" value="move_download">
    <input type="hidden" name="dl_id" value="<?= $dl['id'] ?>">
    <input type="hidden" name="direction" value="down">
</form>
<?php endforeach; ?>

<!-- Add new download -->
<form method="POST" enctype="multipart/form-data" class="card" style="margin-top:12px;background:#fafffe;border:1px dashed #6EBE44;">
    <?= csrf() ?>
    <input type="hidden" name="page" value="nosotros">
    <input type="hidden" name="dl_action" value="add_download">
    <input type="hidden" name="dl_section" value="memorias">

    <h3 style="font-size:15px;margin-bottom:14px;">Agregar descargable</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div>
            <label style="font-size:12px;font-weight:600;">Título</label>
            <input type="text" name="dl_title_new" placeholder="Ej: Descargar Presentación 2024" required
                   style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">Archivo (PDF, DOC, XLS, etc.)</label>
            <input type="file" name="dl_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip" required
                   style="font-size:13px;">
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Agregar descargable</button>
</form>
<?php endif; ?>

<?php layoutEnd(); ?>
