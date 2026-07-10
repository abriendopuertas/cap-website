<?php $titulo = 'Textos'; require_once __DIR__ . '/_header.php';
$content = dataGet('content');
$seccion = $_GET['s'] ?? 'index';

$paginas = [
    'index' => 'Inicio', 'nosotros' => 'Nosotros', 'quehacemos' => 'Qué Hacemos',
    'hazteparte' => 'Hazte Parte', 'contacto' => 'Contacto', 'otec' => 'OTEC', 'footer' => 'Footer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    
    // Handle team member save (from Nosotros page)
    if (isset($_POST['action']) && $_POST['action'] === 'equipo') {
        $grupo = $_POST['grupo'] ?? 'directorio';
        $post_miembros = $_POST['miembros'] ?? [];
        $equipo = dataGet('equipo');
        
        // Ensure uploads directory
        if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
        
        // Process uploaded files
        $uploaded = [];
        if (!empty($_FILES['miembros']['name'])) {
            foreach ($_FILES['miembros']['name'] as $idx => $fields) {
                if (isset($fields['archivo']) && !empty($fields['archivo'])) {
                    $err = $_FILES['miembros']['error'][$idx]['archivo'] ?? UPLOAD_ERR_NO_FILE;
                    if ($err === UPLOAD_ERR_OK) {
                        $tmp = $_FILES['miembros']['tmp_name'][$idx]['archivo'];
                        $ext = strtolower(pathinfo($fields['archivo'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                            $new_name = 'member_' . $grupo . '_' . $idx . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($tmp, UPLOADS_DIR . '/' . $new_name)) {
                                $uploaded[$idx] = 'uploads/equipo/' . $new_name;
                            }
                        }
                    }
                }
            }
        }
        
        $limpios = [];
        foreach ($post_miembros as $i => $m) {
            $imagen = trim($m['imagen'] ?? '');
            if (isset($uploaded[$i])) {
                $old_path = memberImgPath($imagen);
                if ($old_path && file_exists($old_path)) @unlink($old_path);
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
        header('Location: textos.php?s=nosotros'); exit;
    }
    
    // Regular text save — merge para no borrar campos excluidos del form
    $sec = $_POST['seccion'] ?? $seccion;
    $existente = $content[$sec] ?? [];
    $nuevos = $_POST['campos'] ?? [];
    $content[$sec] = array_merge($existente, $nuevos);
    dataSave('content', $content);
    flash('✅ Textos guardados.');
    header("Location: textos.php?s=$sec"); exit;
}
?>
<h1>📝 Editar Páginas</h1>
<div class="tabs">
<?php foreach ($paginas as $k => $v): ?>
    <a href="?s=<?= $k ?>" class="<?= $seccion===$k?'act':'' ?>"><?= $v ?></a>
<?php endforeach; ?>
</div>

<form method="post" class="form">
    <?= csrf() ?><input type="hidden" name="seccion" value="<?= $seccion ?>">
<?php
$campos = $content[$seccion] ?? [];
$labels = [
    'titulo'=>'Título de página', 'subtitulo'=>'Subtítulo',
    'hero_titulo'=>'Título principal', 'hero_descripcion'=>'Descripción principal',
    'historia_titulo'=>'Título historia', 'historia_texto'=>'Texto historia',
    'mision_titulo'=>'Título misión', 'mision_texto'=>'Texto misión',
    'vision_titulo'=>'Título visión', 'vision_texto'=>'Texto visión',
    'intra_titulo'=>'Título sección Intra Penitenciario', 'intra_titulo_2'=>'Título línea 2',
    'intra_subtitulo'=>'Subtítulo Intra',
    'intra_texto_1'=>'Texto 1 Intra', 'intra_texto_2'=>'Texto 2 Intra',
    'intra_texto_3'=>'Texto 3 Intra', 'intra_texto_4'=>'Texto 4 Intra',
    'intra_texto_4b'=>'Texto 4b Intra (continuación)',
    'intra_stat_1_numero'=>'Nº estadística 1 Intra', 'intra_stat_1_texto'=>'Texto estadística 1 Intra',
    'intra_stat_2_numero'=>'Nº estadística 2 Intra', 'intra_stat_2_texto'=>'Texto estadística 2 Intra',
    'abriendo_titulo'=>'Título sección Abriendo Puertas', 'abriendo_titulo_2'=>'Título línea 2',
    'abriendo_subtitulo'=>'Subtítulo Abriendo Puertas',
    'abriendo_texto_1'=>'Texto 1 Abriendo Puertas', 'abriendo_texto_2'=>'Texto 2 Abriendo Puertas',
    'abriendo_texto_3'=>'Texto 3 Abriendo Puertas',
    'abriendo_stat_1_numero'=>'Nº estadística 1 (hijos)', 'abriendo_stat_1_texto'=>'Texto estadística 1',
    'abriendo_stat_2_numero'=>'Nº estadística 2 (mujeres %)', 'abriendo_stat_2_texto'=>'Texto estadística 2',
    'abriendo_stat_3_numero'=>'Nº estadística 3 (años)', 'abriendo_stat_3_texto'=>'Texto estadística 3',
    'productivo_titulo'=>'Título sección Productivo', 'productivo_titulo_2'=>'Título línea 2',
    'productivo_subtitulo'=>'Subtítulo Productivo',
    'productivo_texto_1'=>'Texto Productivo',
    'productivo_link_instagram'=>'Texto link Instagram',
    'libertad_titulo'=>'Título libertad', 'libertad_texto'=>'Texto libertad',
    'proyectos_titulo'=>'Título proyectos', 'proyectos_texto'=>'Texto proyectos',
    'alianzas_titulo'=>'Título alianzas', 'alianzas_texto'=>'Texto alianzas',
    'voluntario_titulo'=>'Título voluntario', 'voluntario_texto'=>'Texto voluntario',
    'donar_titulo'=>'Título donar', 'donar_texto'=>'Texto donar',
    'testimonio_texto'=>'Testimonio', 'testimonio_autor'=>'Autor testimonio',
    'testimonio_voluntario'=>'Testimonio voluntario', 'testimonio_voluntario_autor'=>'Autor testimonio voluntario',
    'testimonio_empresa'=>'Testimonio empresa', 'testimonio_empresa_autor'=>'Autor testimonio empresa',
    'email'=>'Email', 'telefono'=>'Teléfono', 'direccion'=>'Dirección',
    'descripcion'=>'Descripción', 'texto'=>'Texto', 'texto_alternativo'=>'Texto alternativo',
    'directorio_titulo'=>'Título directorio', 'equipo_titulo'=>'Título equipo',
    'imagen_1'=>'Imagen Hero (Fondo principal)',
    'imagen_2'=>'Imagen Historia / Partner',
    'stat_porcentaje'=>'% estadística', 'stat_porcentaje_label'=>'Label %',
    'stat_anos'=>'Años estadística', 'stat_anos_label'=>'Label años',
    'stat_mujeres'=>'Mujeres estadística', 'stat_mujeres_label'=>'Label mujeres',
    'stat_empresas'=>'Empresas estadística', 'stat_empresas_label'=>'Label empresas',
    'hero_titulo_otec'=>'Título hero OTEC', 'hero_texto_otec'=>'Texto hero OTEC',
    'stat_capacitadas'=>'Capacitadas', 'stat_capacitadas_label'=>'Label capacitadas',
    'stat_contratadas'=>'Contratadas', 'stat_contratadas_label'=>'Label contratadas',
    'stat_cursos'=>'Cursos', 'stat_cursos_label'=>'Label cursos',
];
foreach ($campos as $k => $v):
    if (in_array($k, ['donar_url','video_url','mapa_url','boton_donar','boton_donar_url','formulario_action','mensaje_exito','hero_texto',])) continue;
    $label = $labels[$k] ?? ucfirst(str_replace('_',' ',$k));
    $long = strlen($v??'') > 80 || strpos($k, 'texto') !== false || strpos($k, 'descrip') !== false || strpos($k, 'historia') !== false;
?>
    <div class="field">
        <label><?= h($label) ?></label>
        <?php if ($long): ?>
            <textarea name="campos[<?= $k ?>]" rows="4"><?= h($v ?? '') ?></textarea>
        <?php else: ?>
            <input type="text" name="campos[<?= $k ?>]" value="<?= h($v ?? '') ?>">
        <?php endif; ?>
    </div>
<?php endforeach; ?>
    <button type="submit" class="btn">💾 Guardar todos</button>
</form>


<?php 
$equipo = dataGet('equipo');
$eq_grupo = $_GET['eq'] ?? 'directorio';
$eq_miembros = $equipo[$eq_grupo] ?? [];
$es_dir = $eq_grupo === 'directorio';
?>
<hr style="margin:30px 0;border:none;border-top:3px solid #6EBE44;">
<h2 style="margin-bottom:5px;">👥 Directorio y Equipo de Trabajo</h2>
<p style="color:#888;font-size:13px;margin-bottom:15px;">Estos miembros aparecen en la galería de la página Nosotros.</p>

<div class="tabs" style="margin-bottom:15px;">
    <a href="?s=nosotros&eq=directorio" class="<?= $es_dir ? 'act' : '' ?>">📋 Directorio (<?= count($equipo['directorio'] ?? []) ?>)</a>
    <a href="?s=nosotros&eq=equipo" class="<?= !$es_dir ? 'act' : '' ?>">👥 Equipo (<?= count($equipo['equipo'] ?? []) ?>)</a>
</div>

<form method="post" class="form equipo-form" enctype="multipart/form-data">
    <?= csrf() ?>
    <input type="hidden" name="action" value="equipo">
    <input type="hidden" name="grupo" value="<?= $eq_grupo ?>">
    <div id="eq-miembros-container">
    <?php foreach ($eq_miembros as $i => $m): ?>
        <div class="eq-miembro" data-idx="<?= $i ?>">
            <div class="eq-header">
                <strong>👤 <?= h($m['nombre'] ?: 'Miembro ' . ($i+1)) ?></strong>
                <button type="button" class="btn-remove" title="Eliminar" onclick="this.closest('.eq-miembro').remove()">✕</button>
            </div>
            <div class="eq-fields">
                <div class="eq-row">
                    <div class="eq-field" style="flex:2;">
                        <label>Nombre</label>
                        <input type="text" name="miembros[<?= $i ?>][nombre]" value="<?= h($m['nombre']) ?>">
                    </div>
                    <div class="eq-field" style="flex:1;">
                        <label>Foto</label>
                        <div class="eq-foto-wrap">
                            <?php if ($m['imagen']): ?>
                            <img src="<?= h(memberImgUrl($m['imagen'])) ?>" alt="" class="eq-foto-preview" onerror="this.style.display='none'">
                            <?php endif; ?>
                            <label class="eq-file-btn">📁<input type="file" name="miembros[<?= $i ?>][archivo]" accept="image/jpeg,image/png,image/webp,image/gif" onchange="eqPreviewFoto(this)"></label>
                            <input type="text" name="miembros[<?= $i ?>][imagen]" value="<?= h($m['imagen']) ?>" placeholder="URL" style="font-size:11px;padding:4px 6px;">
                        </div>
                    </div>
                </div>
                <div class="eq-field">
                    <label>Cargo / Descripción</label>
                    <textarea name="miembros[<?= $i ?>][cargo]" rows="2"><?= h($m['cargo']) ?></textarea>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <button type="button" class="btn-sec" onclick="eqAddMiembro()" style="margin-bottom:12px;">➕ Agregar miembro</button>
    <br>
    <button type="submit" class="btn">💾 Guardar <?= $es_dir ? 'Directorio' : 'Equipo' ?></button>
</form>

<template id="eq-miembro-tpl">
    <div class="eq-miembro" data-idx="N">
        <div class="eq-header">
            <strong>👤 Nuevo miembro</strong>
            <button type="button" class="btn-remove" title="Eliminar" onclick="this.closest('.eq-miembro').remove()">✕</button>
        </div>
        <div class="eq-fields">
            <div class="eq-row">
                <div class="eq-field" style="flex:2;"><label>Nombre</label><input type="text" name="miembros[N][nombre]"></div>
                <div class="eq-field" style="flex:1;">
                    <label>Foto</label>
                    <div class="eq-foto-wrap">
                        <label class="eq-file-btn">📁<input type="file" name="miembros[N][archivo]" accept="image/jpeg,image/png,image/webp,image/gif" onchange="eqPreviewFoto(this)"></label>
                        <input type="text" name="miembros[N][imagen]" placeholder="URL" style="font-size:11px;padding:4px 6px;">
                    </div>
                </div>
            </div>
            <div class="eq-field"><label>Cargo / Descripción</label><textarea name="miembros[N][cargo]" rows="2"></textarea></div>
        </div>
    </div>
</template>

<script>
let eqNextIdx = <?= count($eq_miembros) ?>;
function eqAddMiembro() {
    const tpl = document.getElementById('eq-miembro-tpl').innerHTML;
    const html = tpl.replace(/\[N\]/g, '[' + eqNextIdx + ']').replace(/data-idx="N"/g, 'data-idx="' + eqNextIdx + '"');
    document.getElementById('eq-miembros-container').insertAdjacentHTML('beforeend', html);
    eqNextIdx++;
}
function eqPreviewFoto(input) {
    const file = input.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const wrap = input.closest('.eq-foto-wrap');
        let img = wrap.querySelector('.eq-foto-preview');
        if (!img) {
            img = document.createElement('img');
            img.className = 'eq-foto-preview';
            img.onerror = function(){ this.style.display='none'; };
            wrap.insertBefore(img, wrap.querySelector('.eq-file-btn'));
        }
        img.src = e.target.result;
        img.style.display = '';
    };
    reader.readAsDataURL(file);
}
</script>


<style>
.tabs { display:flex; gap:0; margin-bottom:20px; border-bottom:2px solid #ddd; flex-wrap:wrap; }
.tabs a { padding:9px 16px; text-decoration:none; color:#888; font-size:13px; border-bottom:2px solid transparent; margin-bottom:-2px; }
.tabs a:hover { color:#6EBE44; } .tabs a.act { color:#6EBE44; border-bottom-color:#6EBE44; font-weight:600; }
.form { background:#fff; padding:25px; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,0.04); }
.field { margin-bottom:14px; }
.field label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:4px; }
.field input, .field textarea { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; box-sizing:border-box; font-family:inherit; }
.field input:focus, .field textarea:focus { border-color:#6EBE44; }
.field textarea { resize:vertical; min-height:70px; }
.btn { background:#6EBE44; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; }
.btn:hover { background:#5da83a; }
.btn-sec { background:#f5f5f5; color:#666; border:1px solid #ddd; padding:8px 18px; border-radius:8px; font-size:13px; cursor:pointer; display:inline-block; }
.btn-sec:hover { background:#eee; }
.btn-remove { background:none; border:none; font-size:16px; color:#999; cursor:pointer; padding:2px 6px; border-radius:4px; }
.btn-remove:hover { background:#ffebee; color:#c62828; }

/* Equipo form */
.equipo-form { margin-top:5px; }
.eq-miembro { background:#f9f9f9; border:1px solid #eee; border-radius:10px; padding:14px; margin-bottom:12px; }
.eq-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.eq-header strong { font-size:14px; }
.eq-fields .eq-field { margin-bottom:8px; }
.eq-fields label { display:block; font-size:12px; font-weight:600; color:#555; margin-bottom:2px; }
.eq-fields input,.eq-fields textarea { width:100%; padding:7px 10px; border:2px solid #e0e0e0; border-radius:6px; font-size:13px; outline:none; box-sizing:border-box; font-family:inherit; }
.eq-fields input:focus,.eq-fields textarea:focus { border-color:#6EBE44; }
.eq-fields textarea { resize:vertical; min-height:40px; }
.eq-row { display:flex; gap:10px; }
.eq-row .eq-field { flex:1; }
.eq-foto-wrap { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.eq-foto-preview { max-width:50px; max-height:50px; border-radius:6px; display:block; border:1px solid #ddd; }
.eq-file-btn { display:inline-flex; align-items:center; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; padding:6px 10px; border-radius:6px; font-size:12px; cursor:pointer; }
.eq-file-btn:hover { background:#c8e6c9; }
.eq-file-btn input[type=file] { display:none; }
@media(max-width:600px){ .eq-row { flex-direction:column; gap:0; } }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
