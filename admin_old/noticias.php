<?php $titulo = 'Noticias'; require_once __DIR__ . '/_header.php';
$noticias = dataGet('noticias');
// Sort by date descending (newest first)
usort($noticias, function($a, $b) {
    $fa = $a['fecha'] ?? '';
    $fb = $b['fecha'] ?? '';
    if ($fa && $fb) return strcmp($fb, $fa);
    if ($fa) return -1;
    if ($fb) return 1;
    return ($b['id'] ?? 0) - ($a['id'] ?? 0);
});

// Define upload dir for news
define('NEWS_UPLOADS', SITE_DIR . '/uploads/noticias');
if (!is_dir(NEWS_UPLOADS)) mkdir(NEWS_UPLOADS, 0755, true);

if (empty($noticias)) { $noticias = []; dataSave('noticias', $noticias); }

// Actions
if (isset($_GET['action'])) {
    $id = (int)($_GET['id'] ?? 0);
    if ($_GET['action'] === 'toggle' && $id) {
        foreach ($noticias as &$n) { if ($n['id'] === $id) { $n['activa'] = !($n['activa'] ?? true); break; } }
        dataSave('noticias', $noticias);
        flash('Estado actualizado.'); header('Location: noticias.php'); exit;
    }
    if ($_GET['action'] === 'delete' && $id) {
        // Delete local images when deleting news
        foreach ($noticias as $n) {
            if ($n['id'] === $id) {
                $img_path = memberImgPath($n['imagen'] ?? '');
                if ($img_path && file_exists($img_path)) @unlink($img_path);
                break;
            }
        }
        $noticias = array_values(array_filter($noticias, fn($n) => $n['id'] !== $id));
        dataSave('noticias', $noticias);
        flash('Noticia eliminada.'); header('Location: noticias.php'); exit;
    }
    if ($_GET['action'] === 'edit' && $id) {
        foreach ($noticias as $n) { if ($n['id'] === $id) $editando = $n; }
    }
    if ($_GET['action'] === 'nueva') {
        $editando = ['id' => 0, 'titulo' => '', 'descripcion' => '', 'imagen' => '', 'link' => '', 'fecha' => date('Y-m-d'), 'activa' => true, 'galeria' => ''];
    }
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $id = (int)($_POST['id'] ?? 0);
    
    // Handle image upload
    $imagen = trim($_POST['imagen'] ?? '');
    if (!empty($_FILES['imagen_archivo']['name']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['imagen_archivo']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['imagen_archivo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $new_name = 'news_' . ($id ?: time()) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($tmp, NEWS_UPLOADS . '/' . $new_name)) {
                // Delete old local file
                if ($id) {
                    foreach ($noticias as $n) {
                        if ($n['id'] === $id) {
                            $old_path = memberImgPath($n['imagen'] ?? '');
                            if ($old_path && file_exists($old_path)) @unlink($old_path);
                            break;
                        }
                    }
                }
                $imagen = 'uploads/noticias/' . $new_name;
            }
        }
    }
    
    // Handle gallery images upload
    $galeria = trim($_POST['galeria'] ?? '');
    // Also check for gallery file uploads (multiple)
    if (!empty($_FILES['galeria_archivos']['name'][0])) {
        $galeria_paths = [];
        $existing = $galeria ? explode("\n", $galeria) : [];
        foreach ($existing as $e) { $e = trim($e); if ($e) $galeria_paths[] = $e; }
        
        foreach ($_FILES['galeria_archivos']['name'] as $i => $name) {
            if (empty($name)) continue;
            if ($_FILES['galeria_archivos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $tmp = $_FILES['galeria_archivos']['tmp_name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $new_name = 'news_gallery_' . ($id ?: time()) . '_' . $i . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp, NEWS_UPLOADS . '/' . $new_name)) {
                    $galeria_paths[] = 'uploads/noticias/' . $new_name;
                }
            }
        }
        $galeria = implode("\n", $galeria_paths);
    }
    
    $item = [
        'titulo' => $_POST['titulo'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
        'imagen' => $imagen,
        'link' => $_POST['link'] ?? '',
        'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
        'activa' => isset($_POST['activa']),
        'galeria' => $galeria,
    ];
    
    if ($id === 0) {
        $max = 0; foreach ($noticias as $n) if ($n['id'] > $max) $max = $n['id'];
        $item['id'] = $max + 1;
        $noticias[] = $item;
        flash('✅ Noticia creada.');
    } else {
        foreach ($noticias as &$n) { if ($n['id'] === $id) { $n = array_merge($n, $item); break; } }
        flash('✅ Noticia actualizada.');
    }
    dataSave('noticias', $noticias);
    header('Location: noticias.php'); exit;
}
?>
<h1>📰 Noticias</h1>
<p style="color:#888;margin-bottom:15px;"><?= count($noticias) ?> noticias · <?= count(array_filter($noticias, fn($n)=>$n['activa']??false)) ?> activas</p>
<a href="?action=nueva" class="btn" style="margin-bottom:20px;">➕ Nueva noticia</a>

<?php if (isset($editando)): $n = $editando; ?>
<div class="modal-backdrop" onclick="this.style.display='none'"></div>
<div class="modal">
    <h2><?= $n['id'] ? 'Editar' : 'Nueva' ?> noticia</h2>
    <form method="post" class="form" enctype="multipart/form-data">
        <?= csrf() ?><input type="hidden" name="id" value="<?= $n['id'] ?>">
        <div class="field"><label>Título</label><input type="text" name="titulo" value="<?= h($n['titulo']) ?>" required></div>
        <div class="field"><label>Descripción</label><textarea name="descripcion" rows="4" required><?= h($n['descripcion']) ?></textarea></div>
        
        <!-- Main image -->
        <div class="field img-field">
            <label>Imagen principal</label>
            <?php if ($n['imagen']): ?>
            <div class="img-preview"><img src="<?= h(memberImgUrl($n['imagen'])) ?>" alt="" onerror="this.style.display='none'"></div>
            <?php endif; ?>
            <div class="img-upload-row">
                <label class="file-btn">📁 Subir imagen<input type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewNoticiaImg(this)"></label>
                <input type="text" name="imagen" value="<?= h($n['imagen']) ?>" placeholder="O pegar URL manualmente">
            </div>
        </div>
        
        <div class="row">
            <div class="field" style="flex:2;"><label>Link del artículo</label><input type="text" name="link" value="<?= h($n['link']) ?>"></div>
            <div class="field" style="max-width:200px;"><label>Fecha</label><input type="date" name="fecha" value="<?= h($n['fecha'] ?? date('Y-m-d')) ?>"></div>
        </div>
        
        <!-- Gallery images -->
        <div class="field">
            <label>Galería de imágenes (opcional)</label>
            <div class="galeria-uploads" id="galeria-uploads">
                <?php 
                $galeria_lista = [];
                if (!empty($n['galeria'])) {
                    $galeria_lista = explode("\n", $n['galeria']);
                    foreach ($galeria_lista as $gimg):
                        $gimg = trim($gimg);
                        if (!$gimg) continue;
                ?>
                <div class="galeria-item">
                    <img src="<?= h(memberImgUrl($gimg)) ?>" alt="" onerror="this.style.display='none'">
                    <input type="text" name="galeria_prev[]" value="<?= h($gimg) ?>" readonly>
                    <button type="button" class="btn-remove-sm" onclick="this.parentElement.remove()">✕</button>
                </div>
                <?php 
                    endforeach;
                }
                ?>
            </div>
            <textarea name="galeria" id="galeria-textarea" rows="2" placeholder="URLs de imágenes adicionales (una por línea)"><?= h($n['galeria'] ?? '') ?></textarea>
            <div style="margin-top:6px;">
                <label class="file-btn-sm">➕ Agregar imágenes desde el computador<input type="file" name="galeria_archivos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple onchange="addGaleriaFiles(this)"></label>
            </div>
            <small style="color:#999;">Puedes subir varias imágenes a la vez o pegar URLs manualmente</small>
        </div>
        
        <div style="display:flex;gap:10px;align-items:center;">
            <label class="chk"><input type="checkbox" name="activa" <?= ($n['activa']??true)?'checked':'' ?>> Noticia activa</label>
            <button type="submit" class="btn">💾 Guardar</button>
            <a href="noticias.php" class="btn-sec">Cancelar</a>
        </div>
    </form>
</div>

<script>
function previewNoticiaImg(input) {
    const file = input.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = input.closest('.img-field').querySelector('.img-preview');
        if (preview) preview.innerHTML = '<img src="' + e.target.result + '" alt="" style="max-width:150px;">';
    };
    reader.readAsDataURL(file);
}

function addGaleriaFiles(input) {
    const files = input.files;
    const textarea = document.getElementById('galeria-textarea');
    const container = document.getElementById('galeria-uploads');
    for (let i = 0; i < files.length; i++) {
        const item = document.createElement('div');
        item.className = 'galeria-item';
        const img = document.createElement('img');
        const reader = new FileReader();
        reader.onload = (function(imgEl, itemEl) {
            return function(e) {
                imgEl.src = e.target.result;
            };
        })(img, item);
        reader.readAsDataURL(files[i]);
        item.appendChild(img);
        const input_hidden = document.createElement('input');
        input_hidden.type = 'text';
        input_hidden.value = '📎 ' + files[i].name;
        input_hidden.readOnly = true;
        item.appendChild(input_hidden);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-remove-sm';
        btn.textContent = '✕';
        btn.onclick = function() { this.parentElement.remove(); };
        item.appendChild(btn);
        container.appendChild(item);
    }
    // The files will be uploaded when the form is submitted
}
</script>
<?php endif; ?>

<div class="lista">
<?php foreach ($noticias as $n): ?>
    <div class="item <?= ($n['activa']??true)?'':'off' ?>">
        <?php if ($n['imagen']): ?>
        <img src="<?= h(memberImgUrl($n['imagen'])) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:6px;flex-shrink:0;" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="info">
            <strong><?= h($n['titulo']) ?></strong>
            <span><?= h(mb_substr($n['descripcion']??'',0,100)) ?><?= mb_strlen($n['descripcion']??'')>100?'...':'' ?></span>
        </div>
        <div class="acc">
            <a href="?action=toggle&id=<?= $n['id'] ?>" title="Activar/Desactivar"><?= ($n['activa']??true)?'👁️':'🚫' ?></a>
            <a href="?action=edit&id=<?= $n['id'] ?>" title="Editar">✏️</a>
            <a href="?action=delete&id=<?= $n['id'] ?>" title="Eliminar" onclick="return confirm('¿Eliminar?')">🗑️</a>
        </div>
    </div>
<?php endforeach; ?>
</div>

<style>
.btn { background:#6EBE44; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
.btn:hover { background:#5da83a; }
.btn-sec { background:#f5f5f5; color:#666; border:none; padding:10px 20px; border-radius:8px; font-size:14px; text-decoration:none; display:inline-block; }
.form { background:#fff; padding:20px; border-radius:10px; margin:15px 0; }
.field { margin-bottom:14px; }
.field label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:4px; }
.field input, .field textarea { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; box-sizing:border-box; }
.field input:focus, .field textarea:focus { border-color:#6EBE44; }
.field textarea { resize:vertical; min-height:60px; }
.row { display:flex; gap:15px; } .row .field { flex:1; }
.chk { display:flex; align-items:center; gap:8px; font-size:14px; cursor:pointer; }

/* Image fields */
.img-field { background:#fafafa; border-radius:8px; padding:12px; border:1px solid #eee; }
.img-preview { margin-bottom:8px; }
.img-preview img { max-width:150px; max-height:120px; border-radius:6px; border:1px solid #ddd; display:block; }
.img-upload-row { display:flex; gap:8px; }
.img-upload-row input { flex:1; }
.file-btn { display:inline-flex; align-items:center; gap:4px; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; white-space:nowrap; }
.file-btn:hover { background:#c8e6c9; }
.file-btn input[type=file] { display:none; }
.file-btn-sm { display:inline-flex; align-items:center; gap:4px; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; padding:6px 12px; border-radius:6px; font-size:12px; cursor:pointer; }
.file-btn-sm:hover { background:#c8e6c9; }
.file-btn-sm input[type=file] { display:none; }
.btn-remove-sm { background:none; border:none; font-size:14px; color:#999; cursor:pointer; padding:2px 6px; border-radius:4px; }
.btn-remove-sm:hover { background:#ffebee; color:#c62828; }

/* Gallery */
.galeria-uploads { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
.galeria-item { display:flex; align-items:center; gap:6px; background:#fff; border:1px solid #eee; border-radius:8px; padding:6px; }
.galeria-item img { width:50px; height:50px; object-fit:cover; border-radius:4px; }
.galeria-item input { flex:1; min-width:100px; border:none; font-size:11px; color:#888; padding:2px; }

/* List */
.lista { display:flex; flex-direction:column; gap:8px; margin-top:15px; }
.item { display:flex; align-items:center; gap:12px; background:#fff; padding:12px 15px; border-radius:10px; border:1px solid #eee; }
.item.off { opacity:0.5; }
.info { flex:1; min-width:0; }
.info strong { display:block; font-size:14px; margin-bottom:2px; }
.info span { font-size:13px; color:#888; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.acc { display:flex; gap:6px; font-size:16px; }
.acc a { text-decoration:none; padding:4px 8px; border-radius:4px; }
.acc a:hover { background:#f0f0f0; }

/* Modal backdrop */
.modal-backdrop { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:999; }
.modal { position:relative; z-index:1000; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.15); margin-bottom:20px; }

@media(max-width:600px){ .img-upload-row { flex-direction:column; } .row { flex-direction:column; gap:0; } }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
