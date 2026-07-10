<?php $titulo = 'Publicar'; require_once __DIR__ . '/_header.php';
$content = dataGet('content');
$config = dataGet('config');

// Merge all data for template replacement
$datos = [];
foreach ($content as $seccion => $campos) {
    foreach ($campos as $k => $v) {
        $datos["content_{$seccion}_{$k}"] = $v;
    }
}
foreach ($config['sitio'] as $k => $v) { $datos["site_{$k}"] = $v; }
foreach ($config['redes'] as $k => $v) { $datos["redes_{$k}"] = $v; }


// === GALLERY RENDERER ===
function renderGallery($grupo) {
    $equipo = dataGet('equipo');
    $miembros = $equipo[$grupo] ?? [];
    if (empty($miembros)) return '';
    
    $html = '<style>
.team-card { position:relative; overflow:hidden; cursor:pointer; aspect-ratio:1/1; background:#f0f0f0; max-width:250px; max-height:250px; justify-self:center; align-self:center; }
.team-card img { width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s; }
.team-card:hover img { transform:scale(1.05); }
.team-card .overlay { position:absolute; bottom:0; left:0; right:0; background:rgba(110,190,68,0.9); color:#fff; padding:15px; transform:translateY(100%); transition:transform 0.3s; }
.team-card:hover .overlay { transform:translateY(0); }
.team-card .overlay .name { font-weight:600; font-size:14px; margin-bottom:4px; }
.team-card .overlay .role { font-size:12px; opacity:0.9; line-height:1.3; }
.galeria-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin:20px auto 40px; max-width:1060px; justify-items:center; }
.galeria-grid.team-5 { grid-template-columns:repeat(3,1fr); }
@media (max-width:800px) { .galeria-grid { grid-template-columns:repeat(2,1fr); } .galeria-grid.team-5 { grid-template-columns:repeat(2,1fr); } }
@media (max-width:500px) { .galeria-grid { grid-template-columns:1fr 1fr; } }
</style>';
    $html .= '<div class="galeria-grid' . (count($miembros) === 5 ? ' team-5' : '') . '">';
    foreach ($miembros as $m) {
        $nombre = h($m['nombre'] ?? '');
        $cargo = h($m['cargo'] ?? '');
        $img = h($m['imagen'] ?? '');
        if ($img && !preg_match('/^https?:\/\//', $img)) {
            if (strpos($img, 'uploads/') === 0) {
                // Local uploaded file
            } elseif (strpos($img, 'images/') === 0) {
                // Local image from /images directory
            } else {
                $img = 'https://static.wixstatic.com/media/' . $img;
            }
        }
        $html .= '<div class="team-card">';
        if ($img) $html .= '<img src="' . $img . '" loading="lazy" alt="' . $nombre . '">';
        $html .= '<div class="overlay"><div class="name">' . $nombre . '</div><div class="role">' . $cargo . '</div></div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// === NEWS RENDERER ===
function renderNoticias() {
    $noticias = dataGet('noticias');
    if (empty($noticias)) return '<p style="text-align:center;color:#888;padding:40px 0;">No hay noticias disponibles.</p>';
    
    // Sort by date descending (newest first), fallback to id desc
    usort($noticias, function($a, $b) {
        $fa = $a['fecha'] ?? '';
        $fb = $b['fecha'] ?? '';
        if ($fa && $fb) return strcmp($fb, $fa);
        if ($fa) return -1;
        if ($fb) return 1;
        return ($b['id'] ?? 0) - ($a['id'] ?? 0);
    });
    
    $html = '<div class="noticias-all" style="max-width:940px; margin:0 auto;">';
    foreach ($noticias as $n) {
        if (!($n['activa'] ?? true)) continue;
        
        $titulo = h($n['titulo'] ?? '');
        $descripcion = h($n['descripcion'] ?? '');
        $imagen = h($n['imagen'] ?? '');
        // Convert image path to URL
        $img_src = $imagen;
        if ($imagen && !preg_match('/^https?:\/\//', $imagen)) {
            if (strpos($imagen, 'uploads/') === 0) {
                $img_src = '../' . $imagen;
            } elseif (strpos($imagen, 'images/') === 0) {
                $img_src = '../' . $imagen;
            } else {
                $img_src = 'https://static.wixstatic.com/media/' . $imagen;
            }
        }
        $link = h($n['link'] ?? '');
        
        // Build with the EXACT same structure as the original static HTML
        $html .= '<div id="noticia-' . $n['id'] . '" class="extra-news-card">
    <a href="' . $link . '" style="display:contents;"><img class="extra-news-card__img" src="' . $img_src . '" loading="lazy" alt="' . $titulo . '"></a>
    <div class="extra-news-card__body">
      <h2 class="extra-news-card__title"><a href="' . $link . '">' . $titulo . '</a></h2>
      <p class="extra-news-card__desc">' . $descripcion . '</p>
    </div>
  </div>';
    }
    $html .= '</div>';
    return $html;
}

$resultados = [];

if (isset($_GET['publicar'])) {
    $archivo = $_GET['publicar'];
    $plantilla = PLANTILLAS_DIR . '/' . $archivo;
    $destino = SITE_DIR . '/' . $archivo;
    
    if (file_exists($plantilla)) {
        $html = file_get_contents($plantilla);
        $reemplazos = 0;
        foreach ($datos as $key => $val) {
            $count = 0;
            $html = str_replace("{{" . $key . "}}", $val, $html, $count);
            $reemplazos += $count;
        }
        // Replace gallery markers
        if (strpos($html, "{{gallery_directorio}}") !== false) {
            $html = str_replace("{{gallery_directorio}}", renderGallery("directorio"), $html, $c);
            $reemplazos += $c;
        }
        if (strpos($html, "{{gallery_equipo}}") !== false) {
            $html = str_replace("{{gallery_equipo}}", renderGallery("equipo"), $html, $c);
            $reemplazos += $c;
        }
        // Replace news list marker
        if (strpos($html, "{{news_list}}") !== false) {
            $html = str_replace("{{news_list}}", renderNoticias(), $html, $c);
            $reemplazos += $c;
        }
        // Remove unused markers (empty them)
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);
        
        if (file_put_contents($destino, $html)) {
            $resultados[] = ['archivo' => $archivo, 'status' => '✅ Publicado', 'reemplazos' => $reemplazos, 'bytes' => filesize($destino)];
        } else {
            $resultados[] = ['archivo' => $archivo, 'status' => '❌ Error al escribir', 'reemplazos' => 0, 'bytes' => 0];
        }
    } else {
        $resultados[] = ['archivo' => $archivo, 'status' => '❌ Plantilla no encontrada', 'reemplazos' => 0, 'bytes' => 0];
    }
    
    if (!empty($resultados)) {
        flash('Publicación completada.');
    }
}

// Get all templates
$plantillas = glob(PLANTILLAS_DIR . '/*.html');
?>
<h1>🚀 Publicar</h1>
<p style="color:#888;margin-bottom:20px;">
    Las plantillas en <code>/admin/plantillas/</code> tienen marcadores <code>{{ variable }}</code>.
    Al publicar, se reemplazan con los valores del panel y se genera el HTML final.
</p>

<?php if (!empty($resultados)): ?>
<div class="results">
<?php foreach ($resultados as $r): ?>
    <div class="res <?= strpos($r['status'],'✅')!==false?'ok':'err' ?>">
        <strong><?= h($r['archivo']) ?></strong> — <?= $r['status'] ?>
        <?php if ($r['reemplazos'] > 0): ?>(<?= $r['reemplazos'] ?> marcadores reemplazados, <?= number_format($r['bytes']) ?> B)<?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="lista">
<?php foreach ($plantillas as $p): $archivo = basename($p); ?>
    <div class="item">
        <div class="info">
            <strong><?= $archivo ?></strong>
            <span><?= number_format(filesize($p)) ?> B — 
            <?php
                $html = file_get_contents($p);
                $marcas = [];
                preg_match_all('/\{\{([^}]+)\}\}/', $html, $marcas);
                $total = count($marcas[1]);
                $dest = SITE_DIR . '/' . $archivo;
                $pub = file_exists($dest) ? 'Publicado: ' . number_format(filesize($dest)) . ' B' : 'No publicado';
                echo "$total marcadores · $pub";
            ?>
            </span>
        </div>
        <a href="?publicar=<?= urlencode($archivo) ?>" class="btn" onclick="return confirm('¿Publicar <?= $archivo ?>?')">🚀 Publicar</a>
    </div>
<?php endforeach; ?>
</div>

<?php if (empty($plantillas)): ?>
<div style="background:#fff3e0;padding:30px;text-align:center;border-radius:12px;color:#e65100;">
    No hay plantillas en <code>/admin/plantillas/</code>.<br>
    Copia los HTML originales a esa carpeta y agrega marcadores <code>{{ variable }}</code> en los textos que quieras editar.
</div>
<?php endif; ?>

<style>
.results { margin-bottom:20px; }
.res { padding:10px 15px; border-radius:8px; margin-bottom:8px; font-size:14px; }
.res.ok { background:#e8f5e9; color:#2e7d32; border-left:3px solid #4caf50; }
.res.err { background:#ffebee; color:#c62828; border-left:3px solid #ef5350; }
.lista { display:flex; flex-direction:column; gap:8px; }
.item { display:flex; align-items:center; gap:12px; background:#fff; padding:14px 18px; border-radius:10px; border:1px solid #eee; }
.info { flex:1; }
.info strong { display:block; font-size:14px; }
.info span { font-size:12px; color:#888; }
.btn { background:#6EBE44; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; white-space:nowrap; }
.btn:hover { background:#5da83a; }
code { background:#f5f5f5; padding:2px 6px; border-radius:4px; font-size:13px; }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
