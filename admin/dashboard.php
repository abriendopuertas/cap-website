<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

$d = db();
$totalNews = $d->query("SELECT COUNT(*) FROM news")->fetchColumn();
$activeNews = $d->query("SELECT COUNT(*) FROM news WHERE active = 1")->fetchColumn();
$totalMedia = $d->query("SELECT COUNT(*) FROM media")->fetchColumn();
$totalFields = $d->query("SELECT COUNT(*) FROM page_content")->fetchColumn();

layoutStart('Dashboard');
?>
<h1>Panel de Administración</h1>
<p>Bienvenido, <?= h(currentUser()['name']) ?>.</p>

<div class="grid">
    <a href="noticias.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="icon">📰</div>
        <div><strong>Noticias</strong><span><?= $activeNews ?> de <?= $totalNews ?> activas</span></div>
    </a>
    <a href="paginas.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="icon">📝</div>
        <div><strong>Páginas</strong><span><?= $totalFields ?> campos editables</span></div>
    </a>
    <a href="media.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="icon">🖼</div>
        <div><strong>Media</strong><span><?= $totalMedia ?> archivos</span></div>
    </a>
    <a href="settings.php" class="card" style="text-decoration:none;color:inherit;">
        <div class="icon">⚙️</div>
        <div><strong>Configuración</strong><span>Contacto, redes sociales</span></div>
    </a>
</div>

<div class="card">
    <h3 style="font-size:16px;margin-bottom:12px;">Estado del sitio</h3>
    <table>
        <tr><td style="color:#888;">Noticias publicadas</td><td style="font-weight:600;"><?= $activeNews ?></td></tr>
        <tr><td style="color:#888;">Campos de contenido</td><td style="font-weight:600;"><?= $totalFields ?></td></tr>
        <tr><td style="color:#888;">Archivos media</td><td style="font-weight:600;"><?= $totalMedia ?></td></tr>
        <tr><td style="color:#888;">Base de datos</td><td style="font-weight:600;"><?= file_exists(DB_PATH) ? round(filesize(DB_PATH)/1024) . ' KB' : 'No creada' ?></td></tr>
    </table>
</div>
<?php layoutEnd(); ?>
