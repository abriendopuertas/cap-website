<?php $titulo = 'Configuración'; require_once __DIR__ . '/_header.php';

// Initialize data files if empty
if (!file_exists(DATA_DIR . '/config.json')) {
    dataSave('config', [
        'sitio' => ['email' => 'capcoordinador@gmail.com', 'telefono' => '+562 2715 1262', 'direccion' => 'Capitán Prat 20, San Joaquín'],
        'redes' => ['facebook' => '', 'instagram' => '', 'twitter' => ''],
        'menu' => [
            'INICIO' => ['archivo' => 'index.html', 'activa' => true],
            'NOSOTROS' => ['archivo' => 'nosotros.html', 'activa' => true],
            'QUÉ HACEMOS' => ['archivo' => 'quehacemos.html', 'activa' => true],
            'NOTICIAS' => ['archivo' => 'noticias.html', 'activa' => true],
            'HAZTE PARTE' => ['archivo' => 'hazteparte.html', 'activa' => true],
            'CONTACTO' => ['archivo' => 'contacto.html', 'activa' => true],
            'OTEC - PROMUEVE' => ['archivo' => 'otec-promueve.html', 'activa' => true]
        ],
        'paginas_extra' => []
    ]);
}
// Initialize content.json if empty
if (!file_exists(DATA_DIR . '/content.json')) {
    dataSave('content', [
        'index' => ['hero_titulo' => '"Yo necesité una segunda oportunidad"', 'stat_anos' => '+23'],
        'nosotros' => ['titulo' => 'NOSOTROS', 'historia_titulo' => 'NUESTRA HISTORIA'],
        'quehacemos' => ['titulo' => 'QUÉ HACEMOS'],
        'hazteparte' => ['titulo' => 'HAZTE PARTE'],
        'contacto' => ['titulo' => 'CONTÁCTANOS'],
        'otec' => ['titulo' => 'OTEC - PROMUEVE'],
        'footer' => ['texto' => '© 2025 Corporación Abriendo Puertas.']
    ]);
}

$config = dataGet('config');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $config['sitio']['email'] = $_POST['email'] ?? '';
    $config['sitio']['telefono'] = $_POST['telefono'] ?? '';
    $config['sitio']['telefono2'] = $_POST['telefono2'] ?? '';
    $config['sitio']['direccion'] = $_POST['direccion'] ?? '';
    $config['redes']['facebook'] = $_POST['facebook'] ?? '';
    $config['redes']['instagram'] = $_POST['instagram'] ?? '';
    $config['redes']['twitter'] = $_POST['twitter'] ?? '';
    dataSave('config', $config);
    flash('✅ Configuración guardada.');
    header('Location: config.php'); exit;
}
?>
<h1>⚙️ Configuración del Sitio</h1>
<form method="post" class="form">
    <?= csrf() ?>
    <h3>Contacto</h3>
    <div class="row">
        <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($config['sitio']['email'] ?? '') ?>"></div>
        <div class="field"><label>Teléfono 1</label><input type="text" name="telefono" value="<?= h($config['sitio']['telefono'] ?? '') ?>"></div>
    </div>
    <div class="row">
        <div class="field"><label>Teléfono 2</label><input type="text" name="telefono2" value="<?= h($config['sitio']['telefono2'] ?? '') ?>"></div>
        <div class="field"><label>Dirección</label><input type="text" name="direccion" value="<?= h($config['sitio']['direccion'] ?? '') ?>"></div>
    </div>
    <h3>Redes Sociales</h3>
    <div class="field"><label>Facebook URL</label><input type="url" name="facebook" value="<?= h($config['redes']['facebook'] ?? '') ?>"></div>
    <div class="row">
        <div class="field"><label>Instagram URL</label><input type="url" name="instagram" value="<?= h($config['redes']['instagram'] ?? '') ?>"></div>
        <div class="field"><label>Twitter URL</label><input type="url" name="twitter" value="<?= h($config['redes']['twitter'] ?? '') ?>"></div>
    </div>
    <button type="submit" class="btn">💾 Guardar</button>
</form>
<style>
.form { background:#fff; padding:25px; border-radius:12px; box-shadow:0 1px 5px rgba(0,0,0,0.04); }
.form h3 { font-size:16px; margin:20px 0 12px; color:#333; padding-bottom:6px; border-bottom:1px solid #eee; }
.form h3:first-of-type { margin-top:0; }
.row { display:flex; gap:15px; }
.row .field { flex:1; }
.field { margin-bottom:15px; }
.field label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:4px; }
.field input { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; box-sizing:border-box; }
.field input:focus { border-color:#6EBE44; }
.btn { background:#6EBE44; color:#fff; border:none; padding:11px 28px; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; margin-top:10px; }
.btn:hover { background:#5da83a; }
@media(max-width:600px){ .row { flex-direction:column; gap:0; } }
</style>
<?php require_once __DIR__ . '/_footer.php'; ?>
