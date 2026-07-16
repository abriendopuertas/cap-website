<?php
// CLI-only publish script — called by .cpanel.yml after deploy
if (php_sapi_name() !== 'cli') { http_response_code(403); exit; }

ob_start();
session_start();
$_SESSION['admin_user'] = ['id' => 1, 'name' => 'deploy'];
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
ob_end_clean();

$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require_once __DIR__ . '/publicar.php';
ob_end_clean();

$d = db();
$results = publishPages($d);
$results = array_merge($results, publishNews($d));

foreach ($results as $r) {
    echo $r['file'] . ': ' . $r['status'] . ' - ' . $r['msg'] . "\n";
}
echo "Deploy publish complete.\n";
