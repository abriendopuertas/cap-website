<?php 
$titulo = 'Equipo'; 
require_once __DIR__ . '/_header.php';

$equipo = dataGet('equipo');
$grupo = $_GET['g'] ?? 'directorio';
$miembros = $equipo[$grupo] ?? [];
$es_directorio = $grupo === 'directorio';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $grupo = $_POST['grupo'] ?? 'directorio';
    $post_miembros = $_POST['miembros'] ?? [];
    
    // Ensure uploads directory exists
    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }
    
    // Process uploaded files
    $uploaded = [];
    if (!empty($_FILES['miembros']['name'])) {
        foreach ($_FILES['miembros']['name'] as $idx => $fields) {
            if (isset($fields['archivo']) && !empty($fields['archivo'])) {
                $err = $_FILES['miembros']['error'][$idx]['archivo'] ?? UPLOAD_ERR_NO_FILE;
                if ($err === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['miembros']['tmp_name'][$idx]['archivo'];
                    $ext = strtolower(pathinfo($fields['archivo'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (in_array($ext, $allowed)) {
                        $new_name = 'member_' . $grupo . '_' . $idx . '_' . time() . '.' . $ext;
                        $dest = UPLOADS_DIR . '/' . $new_name;
                        if (move_uploaded_file($tmp, $dest)) {
                            $uploaded[$idx] = 'uploads/equipo/' . $new_name;
                        }
                    }
                }
            }
        }
    }
    
    // Build clean members list
    $limpios = [];
    foreach ($post_miembros as $i => $m) {
        $imagen = trim($m['imagen'] ?? '');
        // If a new file was uploaded for this member, use it instead
        if (isset($uploaded[$i])) {
            // Delete old local file if exists
            $old_path = memberImgPath($imagen);
            if ($old_path && file_exists($old_path)) {
                @unlink($old_path);
            }
            $imagen = $uploaded[$i];
        }
        $limpios[] = [
            'nombre' => trim($m['nombre'] ?? ''),
            'cargo' => trim($m['cargo'] ?? ''),
            'imagen' => $imagen
        ];
    }
    
    $equipo[$grupo] = $limpios;
    dataSave('equipo', $equipo);
    flash('✅ ' . ($grupo === 'directorio' ? 'Directorio' : 'Equipo') . ' guardado.');
    header('Location: equipo.php?g=' . $grupo);
    exit;
}

// Re-read after potential save
$miembros = $equipo[$grupo] ?? [];
?>
<h1>👥 <?= $es_directorio ? 'Directorio' : 'Equipo de Trabajo' ?></h1>

<div class="tabs">
    <a href="?g=directorio" class="<?= $es_directorio ? 'act' : '' ?>">📋 Directorio (<?= count($equipo['directorio'] ?? []) ?>)</a>
    <a href="?g=equipo" class="<?= !$es_directorio ? 'act' : '' ?>">👥 Equipo (<?= count($equipo['equipo'] ?? []) ?>)</a>
</div>

<form method="post" class="form" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="grupo" value="<?= $grupo ?>">
    <div id="miembros-container">
    <?php foreach ($miembros as $i => $m): ?>
        <div class="miembro" data-idx="<?= $i ?>">
            <div class="miembro-header">
                <strong>👤 <?= h($m['nombre'] ?: 'Miembro ' . ($i+1)) ?></strong>
                <button type="button" class="btn-remove" title="Eliminar miembro" onclick="this.closest('.miembro').remove()">✕</button>
            </div>
            <div class="miembro-fields">
                <div class="field">
                    <label>Nombre</label>
                    <input type="text" name="miembros[<?= $i ?>][nombre]" value="<?= h($m['nombre']) ?>">
                </div>
                <div class="field">
                    <label>Cargo / Descripción</label>
                    <textarea name="miembros[<?= $i ?>][cargo]" rows="2"><?= h($m['cargo']) ?></textarea>
                </div>
                <div class="field foto-field">
                    <label>Foto</label>
                    <div class="foto-preview">
                        <?php if ($m['imagen']): ?>
                            <img src="<?= h(memberImgUrl($m['imagen'])) ?>" alt="" loading="lazy"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div class="foto-inputs">
                        <label class="file-btn">
                            📁 Subir foto
                            <input type="file" name="miembros[<?= $i ?>][archivo]" accept="image/jpeg,image/png,image/webp,image/gif"
                                   onchange="previewFoto(this)">
                        </label>
                        <div class="url-field">
                            <span>O URL manual:</span>
                            <input type="text" name="miembros[<?= $i ?>][imagen]" value="<?= h($m['imagen']) ?>"
                                   placeholder="uploads/equipo/mi_foto.jpg o URL completa">
                        </div>
                    </div>
                    <small class="foto-ayuda">Formatos: jpg, png, webp, gif · Máx 2MB · La foto se sube a nuestro servidor</small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    
    <button type="button" class="btn-sec" onclick="addMiembro()" style="margin-bottom:15px;">➕ Agregar miembro</button>
    <br>
    <button type="submit" class="btn">💾 Guardar <?= $es_directorio ? 'Directorio' : 'Equipo' ?></button>
</form>

<template id="miembro-tpl">
    <div class="miembro" data-idx="N">
        <div class="miembro-header">
            <strong>👤 Nuevo miembro</strong>
            <button type="button" class="btn-remove" title="Eliminar miembro" onclick="this.closest('.miembro').remove()">✕</button>
        </div>
        <div class="miembro-fields">
            <div class="field"><label>Nombre</label><input type="text" name="miembros[N][nombre]"></div>
            <div class="field"><label>Cargo / Descripción</label><textarea name="miembros[N][cargo]" rows="2"></textarea></div>
            <div class="field foto-field">
                <label>Foto</label>
                <div class="foto-preview"></div>
                <div class="foto-inputs">
                    <label class="file-btn">📁 Subir foto<input type="file" name="miembros[N][archivo]" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewFoto(this)"></label>
                    <div class="url-field"><span>O URL manual:</span><input type="text" name="miembros[N][imagen]" placeholder="uploads/equipo/mi_foto.jpg o URL completa"></div>
                </div>
                <small class="foto-ayuda">Formatos: jpg, png, webp, gif · Máx 2MB · La foto se sube a nuestro servidor</small>
            </div>
        </div>
    </div>
</template>

<script>
let nextIdx = <?= count($miembros) ?>;

function addMiembro() {
    const tpl = document.getElementById('miembro-tpl').innerHTML;
    const html = tpl.replace(/\[N\]/g, '[' + nextIdx + ']').replace(/data-idx="N"/g, 'data-idx="' + nextIdx + '"');
    document.getElementById('miembros-container').insertAdjacentHTML('beforeend', html);
    nextIdx++;
}

function previewFoto(input) {
    const file = input.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const miembro = input.closest('.miembro');
        const preview = miembro.querySelector('.foto-preview');
        preview.innerHTML = '<img src="' + e.target.result + '" alt="" style="max-width:100px;max-height:100px;">';
        const urlField = miembro.querySelector('.url-field input');
        if (urlField) urlField.value = '📎 ' + file.name;
    };
    reader.readAsDataURL(file);
}
</script>

<style>
.tabs { display:flex; gap:0; margin-bottom:20px; border-bottom:2px solid #ddd; }
.tabs a { padding:9px 16px; text-decoration:none; color:#888; font-size:14px; border-bottom:2px solid transparent; margin-bottom:-2px; }
.tabs a:hover { color:#6EBE44; } .tabs a.act { color:#6EBE44; border-bottom-color:#6EBE44; font-weight:600; }
.form { background:#fff; padding:25px; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.04); }
.miembro { background:#f9f9f9; border:1px solid #eee; border-radius:10px; padding:18px; margin-bottom:15px; }
.miembro-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
.miembro-header strong { font-size:15px; }
.miembro-fields .field { margin-bottom:10px; }
.miembro-fields label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:3px; }
.miembro-fields input,.miembro-fields textarea { width:100%; padding:8px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; box-sizing:border-box; font-family:inherit; }
.miembro-fields input:focus,.miembro-fields textarea:focus { border-color:#6EBE44; }
.miembro-fields textarea { resize:vertical; min-height:50px; }
.btn-remove { background:none; border:none; font-size:18px; color:#999; cursor:pointer; padding:4px 8px; border-radius:4px; }
.btn-remove:hover { background:#ffebee; color:#c62828; }

/* Photo field */
.foto-field { background:#fff; border-radius:8px; padding:12px; border:1px solid #e8e8e8; }
.foto-field > label { margin-bottom:8px; }
.foto-preview { margin-bottom:8px; }
.foto-preview img { max-width:100px; max-height:100px; border-radius:8px; display:block; border:1px solid #ddd; }
.foto-inputs { display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; }
.file-btn { display:inline-flex; align-items:center; gap:5px; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; white-space:nowrap; }
.file-btn:hover { background:#c8e6c9; }
.file-btn input[type=file] { display:none; }
.url-field { flex:1; min-width:200px; }
.url-field span { display:block; font-size:11px; color:#999; margin-bottom:2px; }
.url-field input { font-size:12px; padding:6px 10px; }
.foto-ayuda { display:block; margin-top:6px; font-size:11px; color:#aaa; }

.btn { background:#6EBE44; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; }
.btn:hover { background:#5da83a; }
.btn-sec { background:#f5f5f5; color:#666; border:1px solid #ddd; padding:10px 22px; border-radius:8px; font-size:14px; cursor:pointer; }
.btn-sec:hover { background:#eee; }

@media(max-width:600px){ .foto-inputs { flex-direction:column; } }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
