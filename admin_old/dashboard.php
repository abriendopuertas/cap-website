<?php $titulo = 'Dashboard'; require_once __DIR__ . '/_header.php';
$config = dataGet('config');
$noticias = dataGet('noticias');
$content = dataGet('content');
$total_noticias = count($noticias);
$activas_noticias = count(array_filter($noticias, fn($n) => $n['activa'] ?? false));
$total_paginas = count($config['menu'] ?? []);
$activas_paginas = count(array_filter($config['menu'] ?? [], fn($p) => $p['activa'] ?? false));
$plantillas = glob(PLANTILLAS_DIR . '/*.html');
$publicadas = 0;
foreach ($plantillas as $p) {
    if (file_exists(SITE_DIR . '/' . basename($p))) $publicadas++;
}
?>
<h1 style="font-size:24px;margin-bottom:4px;">Panel de Administración</h1>
<p style="color:#888;margin-bottom:25px;">Bienvenido, <?= h($_SESSION['admin']['nombre']) ?>.</p>
<div class="grid">
    <a href="noticias.php" class="card"><div class="icon">📰</div><div><strong>Noticias</strong><br><span><?= $activas_noticias ?> de <?= $total_noticias ?> activas</span></div></a>
    <a href="textos.php" class="card"><div class="icon">📝</div><div><strong>Textos del sitio</strong><br><span>Editar contenidos</span></div></a>
    <a href="paginas.php" class="card"><div class="icon">📄</div><div><strong>Páginas</strong><br><span><?= $activas_paginas ?> de <?= $total_paginas ?> activas</span></div></a>
    <a href="config.php" class="card"><div class="icon">⚙️</div><div><strong>Configuración</strong><br><span>Contacto, redes</span></div></a>
    <a href="publicar.php" class="card"><div class="icon">🚀</div><div><strong>Publicar</strong><br><span>Generar HTML desde plantillas</span></div></a>
</div>
<div style="margin-top:25px;background:#fff;border-radius:12px;padding:20px;border:1px solid #eee;">
    <h3 style="font-size:16px;margin-bottom:10px;">📋 Estado</h3>
    <table style="width:100%;font-size:14px;border-collapse:collapse;">
        <tr><td style="padding:5px 0;color:#888;">Plantillas</td><td style="font-weight:600;"><?= count($plantillas) ?></td></tr>
        <tr><td style="padding:5px 0;color:#888;">Páginas publicadas</td><td style="font-weight:600;"><?= $publicadas ?></td></tr>
        <tr><td style="padding:5px 0;color:#888;">Noticias</td><td style="font-weight:600;"><?= $total_noticias ?></td></tr>
        <tr><td style="padding:5px 0;color:#888;">Campos editables</td>
            <td style="font-weight:600;"><?php $c=0; foreach($content as $s) $c+=count($s); echo $c; ?></td></tr>
    </table>
</div>
<style>
.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:15px; }
.card { background:#fff; border-radius:12px; padding:22px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:16px; border:1px solid #eee; transition:0.15s; }
.card:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(0,0,0,0.08); }
.card .icon { font-size:30px; }
.card strong { font-size:15px; display:block; margin-bottom:2px; }
.card span { font-size:13px; color:#888; }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
