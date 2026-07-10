<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $fields = ['site_name','email','form_email','phone1','phone2','address','facebook','instagram','donate_url','copyright'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) settingSave($f, trim($_POST[$f]));
    }
    flash('Configuración guardada.');
    header('Location: settings.php');
    exit;
}

layoutStart('Configuración');
?>
<h1>Configuración del Sitio</h1>
<p>Datos de contacto y redes sociales.</p>

<form method="POST" class="card">
    <?= csrf() ?>
    <h3 style="font-size:16px;margin-bottom:16px;">Contacto</h3>
    <div class="form-row">
        <div class="form-group">
            <label>Nombre del sitio</label>
            <input type="text" name="site_name" value="<?= h(setting('site_name')) ?>">
        </div>
        <div class="form-group">
            <label>Email (visible en el sitio)</label>
            <input type="email" name="email" value="<?= h(setting('email')) ?>">
        </div>
    </div>
    <div class="form-group">
        <label>Email para formularios (donde llegan los datos de contacto, newsletter, etc.)</label>
        <input type="email" name="form_email" value="<?= h(setting('form_email')) ?>" placeholder="<?= h(setting('email')) ?>">
        <small style="color:#888;font-size:12px;">Si se deja vacío, se usará el email visible del sitio.</small>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Teléfono 1</label>
            <input type="text" name="phone1" value="<?= h(setting('phone1')) ?>">
        </div>
        <div class="form-group">
            <label>Teléfono 2</label>
            <input type="text" name="phone2" value="<?= h(setting('phone2')) ?>">
        </div>
    </div>
    <div class="form-group">
        <label>Dirección</label>
        <input type="text" name="address" value="<?= h(setting('address')) ?>">
    </div>

    <h3 style="font-size:16px;margin:24px 0 16px;">Redes Sociales</h3>
    <div class="form-row">
        <div class="form-group">
            <label>Facebook URL</label>
            <input type="url" name="facebook" value="<?= h(setting('facebook')) ?>">
        </div>
        <div class="form-group">
            <label>Instagram URL</label>
            <input type="url" name="instagram" value="<?= h(setting('instagram')) ?>">
        </div>
    </div>

    <h3 style="font-size:16px;margin:24px 0 16px;">Otros</h3>
    <div class="form-row">
        <div class="form-group">
            <label>URL botón Donar</label>
            <input type="url" name="donate_url" value="<?= h(setting('donate_url')) ?>">
        </div>
        <div class="form-group">
            <label>Copyright</label>
            <input type="text" name="copyright" value="<?= h(setting('copyright')) ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
</form>
<?php layoutEnd(); ?>
