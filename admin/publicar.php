<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_layout.php';
requireLogin();

$d = db();

$results = [];
$published = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['pub_action'] ?? '';

    if ($action === 'news') {
        $results = array_merge($results, publishNews($d));
        $published = true;
    } elseif ($action === 'all') {
        $results = array_merge($results, publishNews($d));
        $results = array_merge($results, publishPages($d));
        $published = true;
    } elseif ($action === 'pages') {
        $results = array_merge($results, publishPages($d));
        $published = true;
    }

    if ($published) {
        $ok = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
        $err = count(array_filter($results, fn($r) => $r['status'] === 'error'));
        flash("Publicación completada: $ok archivos actualizados" . ($err ? ", $err errores" : '') . '.');
    }
}

$activeNews = $d->query("SELECT COUNT(*) FROM news WHERE active = 1")->fetchColumn();
$totalNews = $d->query("SELECT COUNT(*) FROM news")->fetchColumn();
$postFiles = count(glob(SITE_DIR . '/post/*.html'));

$settings = [];
foreach ($d->query("SELECT key, value FROM settings") as $r) {
    $settings[$r['key']] = $r['value'];
}

layoutStart('Publicar');
?>
<h1>Publicar sitio</h1>
<p>Genera los archivos HTML estáticos del sitio a partir de los datos del admin.</p>

<div class="grid">
    <div class="card" style="cursor:default;">
        <div class="icon">📰</div>
        <div>
            <strong><?= $activeNews ?> noticias activas</strong>
            <span><?= $totalNews ?> total · <?= $postFiles ?> archivos en /post</span>
        </div>
    </div>
    <div class="card" style="cursor:default;">
        <div class="icon">📄</div>
        <div>
            <strong>7 páginas</strong>
            <span>Inicio, Nosotros, Qué Hacemos, etc.</span>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h2 style="font-size:18px;margin-bottom:16px;">Acciones de publicación</h2>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <form method="POST" style="display:inline;">
            <?= csrf() ?>
            <input type="hidden" name="pub_action" value="news">
            <button type="submit" class="btn btn-primary" onclick="return confirm('¿Publicar noticias? Se regenerarán noticias.html y los archivos en post/')">
                Publicar Noticias
            </button>
        </form>
        <form method="POST" style="display:inline;">
            <?= csrf() ?>
            <input type="hidden" name="pub_action" value="pages">
            <button type="submit" class="btn btn-secondary" onclick="return confirm('¿Actualizar contenido de páginas principales?')">
                Actualizar Páginas
            </button>
        </form>
        <form method="POST" style="display:inline;">
            <?= csrf() ?>
            <input type="hidden" name="pub_action" value="all">
            <button type="submit" class="btn btn-primary" style="background:#2d6a0f;" onclick="return confirm('¿Publicar todo el sitio? Esto regenerará noticias y actualizará páginas.')">
                Publicar Todo
            </button>
        </form>
    </div>
</div>

<?php if ($published && !empty($results)): ?>
<div class="card">
    <h3 style="font-size:16px;margin-bottom:12px;">Resultado de publicación</h3>
    <table>
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Estado</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr>
                <td style="font-family:monospace;font-size:13px;"><?= h($r['file']) ?></td>
                <td>
                    <?php if ($r['status'] === 'ok'): ?>
                    <span class="badge badge-green">OK</span>
                    <?php else: ?>
                    <span class="badge badge-red">Error</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;color:#888;"><?= h($r['msg']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card" style="margin-top:20px;">
    <h3 style="font-size:16px;margin-bottom:8px;">¿Qué se genera?</h3>
    <ul style="font-size:14px;color:#666;line-height:1.8;list-style:disc;padding-left:20px;">
        <li><strong>noticias.html</strong> — Se actualiza el listado de cards con las noticias activas</li>
        <li><strong>post/*.html</strong> — Se genera un HTML limpio por cada noticia activa</li>
        <li><strong>Páginas principales</strong> — Se actualizan textos e imágenes editados en "Páginas"</li>
    </ul>
</div>

<?php
layoutEnd();

// ============================================================
// PUBLISH FUNCTIONS
// ============================================================

function publishNews($d) {
    $results = [];
    $settings = [];
    foreach ($d->query("SELECT key, value FROM settings") as $r) {
        $settings[$r['key']] = $r['value'];
    }

    // 1. Get all active news ordered by date desc
    $news = $d->query("SELECT * FROM news WHERE active = 1 ORDER BY date DESC, id DESC")->fetchAll();

    // 2. Generate noticias.html — replace the noticias-all container
    $noticiasFile = SITE_DIR . '/noticias.html';
    if (file_exists($noticiasFile)) {
        $html = file_get_contents($noticiasFile);

        $cardsHtml = '';
        foreach ($news as $n) {
            $title = htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8');
            $excerpt = htmlspecialchars($n['excerpt'] ?? '', ENT_QUOTES, 'UTF-8');
            $slug = htmlspecialchars($n['slug'], ENT_QUOTES, 'UTF-8');
            $image = htmlspecialchars($n['image'] ?? '', ENT_QUOTES, 'UTF-8');
            $altAttr = htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8');

            $imgTag = $image
                ? '<img class="extra-news-card__img" src="' . $image . '" loading="lazy" alt="' . $altAttr . '">'
                : '<div class="extra-news-card__img" style="background:#ddd;height:200px;"></div>';

            $cardsHtml .= "\n  <div class=\"extra-news-card\">\n"
                . "    $imgTag\n"
                . "    <div class=\"extra-news-card__body\">\n"
                . "      <h2 class=\"extra-news-card__title\"><a href=\"post/{$slug}.html\">{$title}</a></h2>\n"
                . "      <p class=\"extra-news-card__desc\">{$excerpt}</p>\n"
                . "    </div>\n"
                . "  </div>";
        }

        $pattern = '/(<div class="noticias-all"[^>]*>).*?(<\/div>\s*<!--\/\$-->)/s';
        if (preg_match($pattern, $html)) {
            $html = preg_replace($pattern, '$1' . $cardsHtml . "\n" . '$2', $html, 1);
            file_put_contents($noticiasFile, $html);
            $results[] = ['file' => 'noticias.html', 'status' => 'ok', 'msg' => count($news) . ' noticias en el listado'];
        } else {
            // Try simpler pattern
            $startMark = '<div class="noticias-all"';
            $startPos = strpos($html, $startMark);
            if ($startPos !== false) {
                $endTag = findClosingTag($html, $startPos);
                if ($endTag !== false) {
                    $replacement = '<div class="noticias-all" style="max-width:940px; margin:0 auto;">'
                        . $cardsHtml . "\n</div>";
                    $html = substr($html, 0, $startPos) . $replacement . substr($html, $endTag);
                    file_put_contents($noticiasFile, $html);
                    $results[] = ['file' => 'noticias.html', 'status' => 'ok', 'msg' => count($news) . ' noticias en el listado'];
                } else {
                    $results[] = ['file' => 'noticias.html', 'status' => 'error', 'msg' => 'No se encontró el cierre del contenedor'];
                }
            } else {
                $results[] = ['file' => 'noticias.html', 'status' => 'error', 'msg' => 'No se encontró el contenedor noticias-all'];
            }
        }
    } else {
        $results[] = ['file' => 'noticias.html', 'status' => 'error', 'msg' => 'Archivo no encontrado'];
    }

    // 3. Generate individual post HTML files
    $postDir = SITE_DIR . '/post';
    if (!is_dir($postDir)) mkdir($postDir, 0755, true);

    foreach ($news as $n) {
        $slug = $n['slug'];
        $filename = $postDir . '/' . $slug . '.html';

        // Get gallery images
        $imgs = $d->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order");
        $imgs->execute([$n['id']]);
        $gallery = $imgs->fetchAll();

        $postHtml = generatePostHtml($n, $gallery, $settings, $news);
        file_put_contents($filename, $postHtml);
        $results[] = ['file' => 'post/' . $slug . '.html', 'status' => 'ok', 'msg' => 'Generado'];
    }

    return $results;
}

function publishPages($d) {
    $results = [];

    $settings = [];
    foreach ($d->query("SELECT key, value FROM settings") as $r) {
        $settings[$r['key']] = $r['value'];
    }

    // Get all page content grouped by page
    $allContent = [];
    foreach ($d->query("SELECT * FROM page_content ORDER BY page, sort_order") as $row) {
        $allContent[$row['page']][$row['field']] = $row['value'];
    }

    // Map page keys to filenames
    $pageFiles = [
        'index' => 'index.html',
        'nosotros' => 'nosotros.html',
        'quehacemos' => 'quehacemos.html',
        'noticias' => 'noticias.html',
        'hazteparte' => 'hazteparte.html',
        'otec' => 'otec-promueve.html',
        'contacto' => 'contacto.html',
    ];

    // Update footer settings across all pages
    $footerUpdates = [];
    if (!empty($settings['email'])) $footerUpdates[] = ['field' => 'email', 'value' => $settings['email']];
    if (!empty($settings['phone1'])) $footerUpdates[] = ['field' => 'phone1', 'value' => $settings['phone1']];
    if (!empty($settings['phone2'])) $footerUpdates[] = ['field' => 'phone2', 'value' => $settings['phone2']];

    // Build the new header and footer HTML
    $donateUrl = htmlspecialchars($settings['donate_url'] ?? 'https://abriendopuertas.donando.cl/', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($settings['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone1 = htmlspecialchars($settings['phone1'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone2 = htmlspecialchars($settings['phone2'] ?? '', ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($settings['address'] ?? '', ENT_QUOTES, 'UTF-8');
    $copyright = htmlspecialchars($settings['copyright'] ?? '', ENT_QUOTES, 'UTF-8');
    $formEmail = $settings['form_email'] ?? $settings['email'] ?? '';
    $formEmailSafe = htmlspecialchars($formEmail, ENT_QUOTES, 'UTF-8');
    $donateSvg = '<svg viewBox="0 0 65.63 60.33" xmlns="http://www.w3.org/2000/svg" width="22" height="20" style="vertical-align:middle;margin-left:6px;"><g><path d="M31.95 6.81c1.18-1 2.27-1.93 3.38-2.83C40.7-.35 46.6-1.16 52.88 1.6A21 21 0 0 1 65.6 22.24a20.71 20.71 0 0 1-6.06 13.72 3.39 3.39 0 0 1-2.75 1.05 5.88 5.88 0 0 1-6.11 5.16 6 6 0 0 1-1.73 4.38 5.81 5.81 0 0 1-4.28 1.62c-.7 4.28-1.81 5.38-6.09 6.12a18.57 18.57 0 0 1-.3 1.84 5.64 5.64 0 0 1-9.37 2.57c-7.71-7.65-15.46-15.25-23-23a21.19 21.19 0 0 1 7-34.14 16.18 16.18 0 0 1 17.88 4c.37.38.74.79 1.16 1.25Zm-2.16 1.8c-.41-.46-.75-.84-1.09-1.2a13.26 13.26 0 0 0-14.41-3.45 18.41 18.41 0 0 0-6.3 30c5.43 5.57 11 11 16.5 16.52 2.08 2.09 4.16 4.19 6.26 6.25a2.87 2.87 0 0 0 4.08 0 2.92 2.92 0 0 0 .05-4.09c-.3-.33-.63-.65-1-1-1.93-1.92-3.86-3.84-5.77-5.77a1.35 1.35 0 0 1 .36-2.3 1.47 1.47 0 0 1 1.7.45c2.14 2.17 4.3 4.32 6.46 6.48a2.86 2.86 0 0 0 2.59.93 2.74 2.74 0 0 0 2.34-2 2.82 2.82 0 0 0-.74-3c-2.21-2.21-4.42-4.41-6.62-6.63a1.37 1.37 0 0 1-.12-2 1.42 1.42 0 0 1 2.08.08l1 1c1.87 1.87 3.72 3.77 5.62 5.6a2.91 2.91 0 1 0 4-4.2c-1.99-2.11-4.18-4.23-6.28-6.4a2 2 0 0 1-.57-1.37 1.72 1.72 0 0 1 .86-1.08 1.3 1.3 0 0 1 1.55.43c2.19 2.2 4.37 4.4 6.58 6.58a2.91 2.91 0 1 0 4.09-4.13q-5.6-6.27-11.22-12.51c-1.29-1.44-2.59-2.87-3.93-4.35l-.7.52c-3.28 2.51-6.54 5-9.85 7.51a5.7 5.7 0 0 1-7-9c1-.8 1.95-1.6 2.91-2.41Zm33.09 11.72a18.23 18.23 0 0 0-8.51-14.82c-6-3.95-12.68-3.57-17.76 1-1.57 1.41-3.24 2.73-4.86 4.08l-9.63 8a2.83 2.83 0 0 0-1.15 2.92 2.87 2.87 0 0 0 4.65 1.71c3.74-2.82 7.45-5.67 11.17-8.52 1-.79 1.59-.77 2.47.19q2.47 2.7 4.93 5.42l12.21 13.4c.4.44.73.74 1.28.17a18.7 18.7 0 0 0 5.2-13.55Z" fill="#ffffff"/></g></svg>';

    // Active page map for nav highlighting
    $activePages = [
        'index' => 'index.html',
        'nosotros' => 'nosotros.html',
        'quehacemos' => 'quehacemos.html',
        'noticias' => 'noticias.html',
        'hazteparte' => 'hazteparte.html',
        'otec' => 'otec-promueve.html',
        'contacto' => 'contacto.html',
    ];

    $navLinks = [
        'index' => 'Inicio',
        'nosotros' => 'Nosotros',
        'quehacemos' => 'Qué Hacemos',
        'noticias' => 'Noticias',
        'hazteparte' => 'Hazte Parte',
        'otec' => 'OTEC - Promueve',
    ];

    $headerFooterStyle = <<<STYLE
<style data-cap-global="true">
    @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap');
    .cap-header { background:#616161 !important; padding:0 !important; position:sticky !important; top:0 !important; z-index:100 !important; box-shadow:0 0 5px rgba(0,0,0,0.7) !important; }
    .cap-header__inner { display:flex !important; align-items:center !important; height:108px !important; padding:0 100px !important; }
    .cap-header__logo { flex-shrink:0 !important; }
    .cap-header__logo img { height:100px !important; width:auto !important; }
    .cap-header__nav { display:flex !important; align-items:center !important; gap:2px !important; flex:1 !important; justify-content:center !important; }
    .cap-header__nav a { font-family:'Open Sans',sans-serif !important; font-size:15px !important; font-weight:400 !important; text-transform:uppercase !important; color:#fff !important; padding:10px 14px !important; transition:color .2s !important; text-decoration:none !important; height:auto !important; line-height:normal !important; }
    .cap-header__nav a:hover { color:#6EBE44 !important; text-decoration:none !important; }
    .cap-header__nav a.active { color:#6EBE44 !important; }
    .cap-header__donate { display:inline-flex !important; align-items:center !important; background:#6EBE44 !important; color:#fff !important; padding:10px 18px !important; border-radius:0 !important; font-weight:400 !important; font-size:15px !important; font-family:'Open Sans',sans-serif !important; transition:background .2s !important; text-decoration:none !important; flex-shrink:0 !important; text-transform:uppercase !important; height:auto !important; line-height:normal !important; }
    .cap-header__donate:hover { background:#5aa636 !important; text-decoration:none !important; }
    .cap-footer { background:#616161 !important; color:#fff !important; padding:36px 24px 24px !important; }
    .cap-footer__inner { max-width:1100px !important; margin:0 auto !important; display:flex !important; justify-content:space-between !important; align-items:flex-start !important; }
    .cap-footer__newsletter { flex:1 !important; }
    .cap-footer__newsletter p { color:#6EBE44 !important; font-size:16px !important; margin-bottom:12px !important; font-family:'Open Sans',sans-serif !important; }
    .cap-footer__newsletter-form { display:flex !important; gap:8px !important; max-width:400px !important; }
    .cap-footer__newsletter-form input { flex:1 !important; padding:10px 14px !important; border:1px solid #888 !important; border-radius:4px !important; background:transparent !important; color:#fff !important; font-size:14px !important; font-family:'Open Sans',sans-serif !important; }
    .cap-footer__newsletter-form input::placeholder { color:#aaa !important; }
    .cap-footer__newsletter-form button { background:#6EBE44 !important; color:#fff !important; border:none !important; padding:10px 22px !important; border-radius:4px !important; font-size:14px !important; cursor:pointer !important; font-family:'Open Sans',sans-serif !important; }
    .cap-footer__contact { text-align:right !important; font-family:'Open Sans',sans-serif !important; }
    .cap-footer__contact p { font-size:14px !important; color:#ddd !important; line-height:1.8 !important; }
    .cap-footer__bottom { max-width:1100px !important; margin:20px auto 0 !important; padding-top:16px !important; border-top:1px solid #888 !important; text-align:center !important; font-size:13px !important; color:#ccc !important; font-family:'Open Sans',sans-serif !important; }
    @media (max-width:768px) {
        .cap-header__nav { display:none; }
        .cap-header__inner { height:70px; }
        .cap-header__logo img { height:60px; }
        .cap-header__inner { padding:0 20px; }
        .cap-footer__inner { flex-direction:column; }
        .cap-footer__contact { text-align:left; }
    }
</style>
STYLE;

    foreach ($pageFiles as $pageKey => $filename) {
        $filepath = SITE_DIR . '/' . $filename;
        if (!file_exists($filepath)) {
            $results[] = ['file' => $filename, 'status' => 'error', 'msg' => 'Archivo no encontrado'];
            continue;
        }

        $html = file_get_contents($filepath);
        $changed = false;
        $updateCount = 0;

        // Apply page-specific content updates
        $fields = $allContent[$pageKey] ?? [];
        if (!empty($fields)) {
            $updateCount = applyPageUpdates($html, $pageKey, $fields, $changed);
        }

        // Replace team galleries on nosotros page
        if ($pageKey === 'nosotros') {
            $galleryMap = [
                'directorio' => 'comp-llgww2fr',
                'equipo' => 'comp-llzf3p11',
            ];
            foreach ($galleryMap as $section => $galleryId) {
                $members = $d->prepare("SELECT * FROM team_members WHERE section = ? ORDER BY sort_order");
                $members->execute([$section]);
                $members = $members->fetchAll();
                if (empty($members)) continue;

                $marker = "data-cap-team=\"{$section}\"";
                $teamHtml = generateTeamHtml($members, $section);

                if (strpos($html, $marker) !== false) {
                    $html = preg_replace(
                        '/<div ' . preg_quote($marker, '/') . '>.*?<\/div><!-- \/cap-team -->/s',
                        $teamHtml, $html, 1, $c
                    );
                    if ($c > 0) { $changed = true; $updateCount++; }
                } else {
                    $galleryTag = "<div id=\"{$galleryId}\"";
                    $pos = strpos($html, $galleryTag);
                    if ($pos !== false) {
                        $end = findClosingTag($html, $pos);
                        if ($end !== false) {
                            $hideStyle = '<style>[id="' . $galleryId . '"]{display:none!important}</style>';
                            $html = substr($html, 0, $end) . $hideStyle . $teamHtml . substr($html, $end);
                            $changed = true;
                            $updateCount++;
                        }
                    }
                }
            }
        }

        // Replace downloads on nosotros page
        if ($pageKey === 'nosotros') {
            $dlItems = $d->prepare("SELECT * FROM downloads WHERE section = 'memorias' ORDER BY sort_order");
            $dlItems->execute();
            $dlItems = $dlItems->fetchAll();
            if (!empty($dlItems)) {
                $dlHtml = generateDownloadsHtml($dlItems);
                $dlMarker = 'data-cap-downloads="memorias"';
                $wixDownloadIds = [
                    'comp-lm10ccp6', 'comp-lm10o1mo', 'comp-lm10odj2',
                    'comp-lm10p47n', 'comp-lm10ps2r', 'comp-lm10q6ea',
                ];

                if (strpos($html, $dlMarker) !== false) {
                    $html = preg_replace(
                        '/<div ' . preg_quote($dlMarker, '/') . '>.*?<\/div><!-- \/cap-downloads -->/s',
                        $dlHtml, $html, 1, $c
                    );
                    if ($c > 0) { $changed = true; $updateCount++; }
                } else {
                    $hideStyles = '';
                    foreach ($wixDownloadIds as $wid) {
                        $hideStyles .= '<style>[id="' . $wid . '"]{display:none!important}</style>';
                    }
                    $lastId = end($wixDownloadIds);
                    $lastTag = "<div id=\"{$lastId}\"";
                    $pos = strpos($html, $lastTag);
                    if ($pos !== false) {
                        $end = findClosingTag($html, $pos);
                        if ($end !== false) {
                            $html = substr($html, 0, $end) . $hideStyles . $dlHtml . substr($html, $end);
                            $changed = true;
                            $updateCount++;
                        }
                    }
                }
            }
        }

        // Replace contact form on contacto page
        if ($pageKey === 'contacto' && $formEmail) {
            $contactMarker = 'data-cap-form="contacto"';
            $wixFormId = 'comp-llgxd3ho';
            $contactFormHtml = generateContactFormHtml($formEmailSafe);

            if (strpos($html, $contactMarker) !== false) {
                $html = preg_replace(
                    '/<div ' . preg_quote($contactMarker, '/') . '>.*<!-- \/cap-form -->/s',
                    $contactFormHtml, $html, 1, $c
                );
                if ($c > 0) { $changed = true; $updateCount++; }
            } else {
                $formTag = "<div id=\"{$wixFormId}\"";
                $pos = strpos($html, $formTag);
                if ($pos !== false) {
                    $end = findClosingTag($html, $pos);
                    if ($end !== false) {
                        $hideStyle = '<style>[id="' . $wixFormId . '"]{display:none!important}</style>';
                        $html = substr($html, 0, $end) . $hideStyle . $contactFormHtml . substr($html, $end);
                        $changed = true;
                        $updateCount++;
                    }
                }
            }
        }

        // Build nav with active page highlighted
        $navHtml = '';
        foreach ($navLinks as $nk => $label) {
            $href = ($nk === $pageKey) ? '#' : $activePages[$nk];
            $cls = ($nk === $pageKey) ? ' class="active"' : '';
            $navHtml .= "<a href=\"{$href}\"{$cls}>{$label}</a>";
        }

        $newHeader = '<header class="cap-header"><div class="cap-header__inner">'
            . '<a href="index.html" class="cap-header__logo"><img src="_wix_assets/59b3c6c9e029fd11.png" alt="Corporación Abriendo Puertas"></a>'
            . '<nav class="cap-header__nav">' . $navHtml . '</nav>'
            . "<a href=\"{$donateUrl}\" target=\"_blank\" class=\"cap-header__donate\">Donar ahora{$donateSvg}</a>"
            . '</div></header>';

        $newFooter = '<footer class="cap-footer"><div class="cap-footer__inner">'
            . '<div class="cap-footer__newsletter">'
            . '<p>Suscr&iacute;bete a nuestra newsletter</p>'
            . "<form class=\"cap-footer__newsletter-form\" action=\"https://formsubmit.co/{$formEmailSafe}\" method=\"POST\">"
            . '<input type="hidden" name="_subject" value="Nueva suscripción newsletter">'
            . '<input type="hidden" name="_captcha" value="false">'
            . '<input type="hidden" name="_template" value="table">'
            . '<input type="hidden" name="_next" value="https://www.corporacionabriendopuertas.cl/contacto.html?newsletter=ok">'
            . '<input type="email" name="email" placeholder="Ingresa tu email aqu&iacute;*" required>'
            . '<button type="submit">Unirse</button>'
            . '</form></div>'
            . '<div class="cap-footer__contact">'
            . "<p>{$email}</p><p>{$phone1}&nbsp;&nbsp;{$phone2}</p><p>{$address}</p>"
            . '</div></div>'
            . "<div class=\"cap-footer__bottom\">{$copyright}</div>"
            . '</footer>';

        // Replace header (Wix original or previously injected cap-header)
        $headerStart = strpos($html, '<header id="SITE_HEADER"');
        if ($headerStart === false) {
            $headerStart = strpos($html, '<header class="cap-header"');
        }
        if ($headerStart !== false) {
            $headerEnd = strpos($html, '</header>', $headerStart);
            if ($headerEnd !== false) {
                $headerEnd += strlen('</header>');
                $html = substr($html, 0, $headerStart) . $newHeader . substr($html, $headerEnd);
                $changed = true;
                $updateCount++;
            }
        }

        // Replace footer (Wix original or previously injected cap-footer)
        $footerStart = strpos($html, '<footer id="SITE_FOOTER"');
        if ($footerStart === false) {
            $footerStart = strpos($html, '<footer class="cap-footer"');
        }
        if ($footerStart !== false) {
            $footerEnd = strpos($html, '</footer>', $footerStart);
            if ($footerEnd !== false) {
                $footerEnd += strlen('</footer>');
                $html = substr($html, 0, $footerStart) . $newFooter . substr($html, $footerEnd);
                $changed = true;
                $updateCount++;
            }
        }

        // Remove old Wix favicons and inject new ones
        $html = preg_replace('/<link[^>]*rel=["\'](?:icon|shortcut icon|apple-touch-icon)["\'][^>]*>\s*/i', '', $html);
        $headEnd = strpos($html, '</head>');
        if ($headEnd !== false) {
            $faviconTags = '<link rel="icon" type="image/x-icon" href="favicon.ico">'
                . '<link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png">'
                . '<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">';
            $html = substr($html, 0, $headEnd) . $faviconTags . substr($html, $headEnd);
            $changed = true;
        }

        // Inject global style if not already present
        if (strpos($html, 'data-cap-global="true"') === false) {
            $headEnd = strpos($html, '</head>');
            if ($headEnd !== false) {
                $html = substr($html, 0, $headEnd) . $headerFooterStyle . substr($html, $headEnd);
                $changed = true;
            }
        } else {
            // Update existing style block
            $styleStart = strpos($html, '<style data-cap-global="true">');
            if ($styleStart !== false) {
                $styleEnd = strpos($html, '</style>', $styleStart) + strlen('</style>');
                $html = substr($html, 0, $styleStart) . $headerFooterStyle . substr($html, $styleEnd);
                $changed = true;
            }
        }

        // Update FormSubmit email in ALL forms across the page
        if ($formEmail) {
            $newHtml = preg_replace(
                '#(action="https://formsubmit\.co/)[^"]+#',
                '${1}' . $formEmailSafe,
                $html, -1, $emailCount
            );
            if ($emailCount > 0 && $newHtml !== $html) {
                $html = $newHtml;
                $changed = true;
                $updateCount += $emailCount;
            }
        }

        if ($changed) {
            file_put_contents($filepath, $html);
            $results[] = ['file' => $filename, 'status' => 'ok', 'msg' => "$updateCount elementos actualizados"];
        } else {
            $results[] = ['file' => $filename, 'status' => 'ok', 'msg' => 'Sin cambios necesarios'];
        }
    }

    return $results;
}

function applyPageUpdates(&$html, $pageKey, $fields, &$changed) {
    $count = 0;
    $d = db();

    $settings = [];
    foreach ($d->query("SELECT key, value FROM settings") as $r) {
        $settings[$r['key']] = $r['value'];
    }

    // Update donate link across all pages
    if (!empty($settings['donate_url'])) {
        $newHtml = preg_replace(
            '/href="https?:\/\/[^"]*donando[^"]*"/',
            'href="' . htmlspecialchars($settings['donate_url'], ENT_QUOTES) . '"',
            $html, -1, $c
        );
        if ($c > 0) { $html = $newHtml; $changed = true; $count += $c; }
    }

    // Update social links
    if (!empty($settings['facebook'])) {
        $newHtml = preg_replace(
            '/href="https?:\/\/[^"]*facebook\.com[^"]*"/',
            'href="' . htmlspecialchars($settings['facebook'], ENT_QUOTES) . '"',
            $html, -1, $c
        );
        if ($c > 0) { $html = $newHtml; $changed = true; $count += $c; }
    }

    if (!empty($settings['instagram'])) {
        $newHtml = preg_replace(
            '/href="https?:\/\/[^"]*instagram\.com[^"]*"/',
            'href="' . htmlspecialchars($settings['instagram'], ENT_QUOTES) . '"',
            $html, -1, $c
        );
        if ($c > 0) { $html = $newHtml; $changed = true; $count += $c; }
    }

    // Apply page content field replacements
    $stmt = $d->prepare("SELECT field, value, published_value FROM page_content WHERE page = ?");
    $stmt->execute([$pageKey]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $newVal = $row['value'] ?? '';
        $oldVal = $row['published_value'] ?? $newVal;
        if ($oldVal === $newVal && strpos($html, htmlspecialchars($newVal, ENT_QUOTES, 'UTF-8')) !== false) {
            continue;
        }

        $oldEncoded = htmlspecialchars($oldVal, ENT_QUOTES, 'UTF-8');
        $newEncoded = htmlspecialchars($newVal, ENT_QUOTES, 'UTF-8');

        $replaced = false;

        // Try replacing as a text node: >oldText<
        $pattern = '>' . preg_quote($oldEncoded, '/') . '<';
        $replacement = '>' . $newEncoded . '<';
        $newHtml = preg_replace('/' . $pattern . '/', $replacement, $html, 1, $c);
        if ($c > 0) {
            $html = $newHtml;
            $replaced = true;
        }

        // Also try with HTML entities that Wix uses (&eacute;, &oacute;, etc.)
        if (!$replaced) {
            $oldHtmlEntities = mb_encode_numericentity($oldVal, [0x80, 0xffff, 0, 0xffff], 'UTF-8');
            $oldWixEncoded = str_replace(
                ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ','ü','Ü','"','"'],
                ['&aacute;','&eacute;','&iacute;','&oacute;','&uacute;','&ntilde;','&Aacute;','&Eacute;','&Iacute;','&Oacute;','&Uacute;','&Ntilde;','&uuml;','&Uuml;','&quot;','&quot;'],
                $oldVal
            );
            if ($oldWixEncoded !== $oldVal) {
                $pattern = '>' . preg_quote($oldWixEncoded, '/') . '<';
                $replacement = '>' . $newEncoded . '<';
                $newHtml = preg_replace('/' . $pattern . '/', $replacement, $html, 1, $c);
                if ($c > 0) {
                    $html = $newHtml;
                    $replaced = true;
                }
            }
        }

        if ($replaced) {
            $changed = true;
            $count++;
            $d->prepare("UPDATE page_content SET published_value = ? WHERE page = ? AND field = ?")
              ->execute([$newVal, $pageKey, $row['field']]);
        }
    }

    // Replace stats sections dynamically
    $statGroups = $d->prepare("SELECT DISTINCT stat_group FROM page_stats WHERE page = ?");
    $statGroups->execute([$pageKey]);
    $statGroups = $statGroups->fetchAll(PDO::FETCH_COLUMN);

    foreach ($statGroups as $sg) {
        $stats = $d->prepare("SELECT number, text FROM page_stats WHERE page = ? AND stat_group = ? ORDER BY sort_order");
        $stats->execute([$pageKey, $sg]);
        $stats = $stats->fetchAll();

        if (empty($stats)) continue;

        $marker = "data-cap-stats=\"{$pageKey}-{$sg}\"";
        $statsHtml = generateStatsHtml($stats, $pageKey, $sg);

        if (strpos($html, $marker) !== false) {
            $html = preg_replace(
                '/<div ' . preg_quote($marker, '/') . '>.*?<\/div><!-- \/cap-stats -->/s',
                $statsHtml,
                $html, 1, $c
            );
            if ($c > 0) { $changed = true; $count++; }
        } else {
            // First time: find original Wix stats and inject replacement
            $wixStatsMap = [
                'index-main' => ['id' => 'comp-lmjjc7h0', 'tag' => 'div'],
                'quehacemos-intra' => ['id' => 'comp-lms4pzqo', 'tag' => 'section'],
                'quehacemos-libertad' => ['id' => 'comp-lms4rqud', 'tag' => 'section'],
            ];
            $wixInfo = $wixStatsMap["{$pageKey}-{$sg}"] ?? null;
            if ($wixInfo) {
                $wixId = $wixInfo['id'];
                $wixTag = $wixInfo['tag'];
                $openTag = "<{$wixTag} id=\"{$wixId}\"";
                $closeTag = "</{$wixTag}>";
                $elStart = strpos($html, $openTag);
                if ($elStart !== false) {
                    $elEnd = findClosingTag($html, $elStart, $wixTag);
                    if ($elEnd !== false) {
                        $hideStyle = '<style>[id="' . $wixId . '"]{display:none!important}</style>';
                        $html = substr($html, 0, $elEnd) . $hideStyle . $statsHtml . substr($html, $elEnd);
                        $changed = true;
                        $count++;
                    }
                }
            }
        }
    }

    return $count;
}

function generateTeamHtml($members, $section) {
    $items = '';
    foreach ($members as $m) {
        $name = htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($m['role'], ENT_QUOTES, 'UTF-8');
        $img = htmlspecialchars($m['image'], ENT_QUOTES, 'UTF-8');
        $imgTag = $img
            ? '<img src="' . $img . '" alt="' . $name . '" style="width:100%;height:100%;object-fit:cover;display:block;">'
            : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#e0e0e0;color:#999;font-size:32px;">?</div>';
        $items .= <<<ITEM
<div class="cap-team-card">
    {$imgTag}
    <div class="cap-team-hover">
        <div class="cap-team-hover__name">{$name}</div>
        <div class="cap-team-hover__role">{$role}</div>
    </div>
</div>
ITEM;
    }

    $padBottom = '70px';
    $marker = "data-cap-team=\"{$section}\"";
    return <<<HTML
<div {$marker}>
<style>
.cap-team-grid { display:flex; flex-wrap:wrap; justify-content:center; gap:22px; max-width:850px; margin:0 auto; padding:10px 0; }
.cap-team-grid[data-section="{$section}"] { padding-bottom:{$padBottom}; }
.cap-team-card { width:195px; height:194px; position:relative; overflow:hidden; background:#f0f0f0; }
.cap-team-card img { width:100%; height:100%; object-fit:cover; }
.cap-team-hover { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(83,143,51,0.85); display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:12px 8px; box-sizing:border-box; opacity:0; transition:opacity 0.3s; z-index:10; }
.cap-team-card:hover .cap-team-hover { opacity:1; }
.cap-team-hover__name { font-size:15px; font-weight:700; color:#fff; margin-bottom:6px; }
.cap-team-hover__role { font-size:12px; color:#e8fce0; line-height:1.4; }
@media (max-width:768px) {
    .cap-team-grid { gap:12px; }
    .cap-team-card { width:160px; height:160px; }
}
</style>
<div class="cap-team-grid" data-section="{$section}">{$items}</div>
</div><!-- /cap-team -->
HTML;
}

function generateContactFormHtml($formEmail) {
    return <<<HTML
<div data-cap-form="contacto">
<style>
.cap-contact-form { max-width:480px; margin:0 auto; padding:10px 0 30px; }
.cap-contact-form input,
.cap-contact-form textarea { width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:0; font-size:15px; font-family:'HelveticaNeueW01-Thin',Helvetica,Arial,sans-serif; color:#333; background:#fff; box-sizing:border-box; margin-bottom:10px; outline:none; transition:border-color 0.2s; }
.cap-contact-form input:focus,
.cap-contact-form textarea:focus { border-color:#6EBE44; }
.cap-contact-form textarea { height:120px; resize:vertical; }
.cap-contact-form button { display:block; width:100%; padding:14px; background:#6EBE44; color:#fff; border:none; font-size:16px; font-family:'HelveticaNeueW01-Thin',Helvetica,Arial,sans-serif; cursor:pointer; transition:background 0.2s; letter-spacing:0.5px; }
.cap-contact-form button:hover { background:#5aa838; }
.cap-contact-msg { text-align:center; padding:16px; color:#6EBE44; font-size:15px; display:none; }
</style>
<form class="cap-contact-form" action="https://formsubmit.co/{$formEmail}" method="POST">
    <input type="hidden" name="_subject" value="Nuevo mensaje de contacto — CAP">
    <input type="hidden" name="_captcha" value="false">
    <input type="hidden" name="_template" value="table">
    <input type="hidden" name="_next" value="https://www.corporacionabriendopuertas.cl/contacto.html?enviado=ok">
    <input type="text" name="_honey" style="display:none">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="asunto" placeholder="Asunto">
    <textarea name="mensaje" placeholder="Escribe tu mensaje aquí..."></textarea>
    <button type="submit">Enviar</button>
</form>
<div class="cap-contact-msg" id="cap-contact-ok">¡Gracias por tu mensaje!</div>
<script>if(location.search.includes('enviado=ok')){document.querySelector('.cap-contact-form').style.display='none';document.getElementById('cap-contact-ok').style.display='block';}</script>
</div><!-- /cap-form -->
HTML;
}

function generateDownloadsHtml($items) {
    $cards = '';
    foreach ($items as $dl) {
        $title = htmlspecialchars($dl['title'], ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($dl['file_path'], ENT_QUOTES, 'UTF-8');
        $cards .= <<<CARD
<a href="{$href}" target="_blank" class="cap-dl-card">
    <div class="cap-dl-icon">
        <svg viewBox="0 0 24 24" fill="none" width="36" height="36"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div class="cap-dl-title">{$title}</div>
</a>
CARD;
    }
    return <<<HTML
<div data-cap-downloads="memorias">
<style>
.cap-dl-grid { display:flex; flex-wrap:wrap; justify-content:center; gap:18px; max-width:850px; margin:0 auto; padding:10px 0 40px; }
.cap-dl-card { display:flex; flex-direction:column; align-items:center; justify-content:center; width:170px; height:140px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18); border-radius:10px; text-decoration:none; padding:16px 12px; box-sizing:border-box; transition:background 0.25s, border-color 0.25s; }
.cap-dl-card:hover { background:rgba(110,190,68,0.18); border-color:rgba(110,190,68,0.5); }
.cap-dl-icon { margin-bottom:10px; opacity:0.85; }
.cap-dl-card:hover .cap-dl-icon { opacity:1; }
.cap-dl-title { color:#fff; font-size:13px; text-align:center; line-height:1.35; font-family:"HelveticaNeueW01-Thin","HelveticaNeueW02-Thin",Helvetica,Arial,sans-serif; }
@media (max-width:768px) { .cap-dl-grid { gap:12px; } .cap-dl-card { width:145px; height:125px; } .cap-dl-title { font-size:12px; } }
</style>
<div class="cap-dl-grid">{$cards}</div>
</div><!-- /cap-downloads -->
HTML;
}

function generateStatsHtml($stats, $page, $group) {
    $n = count($stats);
    $items = '';
    foreach ($stats as $s) {
        $num = htmlspecialchars($s['number'], ENT_QUOTES, 'UTF-8');
        $raw = $s['text'];
        $greenSpan = '<span style="color:#6EBE44;font-weight:700;">';
        if (preg_match('/\*([^*]+)\*/', $raw, $m)) {
            $txt = htmlspecialchars(preg_replace('/\*([^*]+)\*/', '$1', $raw), ENT_QUOTES, 'UTF-8');
            $word = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $txt = str_replace($word, $greenSpan . $word . '</span>', $txt);
        } else {
            $txt = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
            $words = explode(' ', $txt, 2);
            $txt = $greenSpan . $words[0] . '</span>' . (isset($words[1]) ? ' ' . $words[1] : '');
        }

        $hasNum = trim($num) !== '';
        $items .= '<div class="cap-stat-item">'
            . ($hasNum ? '<div class="cap-stat-number">' . $num . '</div>' : '')
            . '<div class="cap-stat-text">' . $txt . '</div>'
            . '</div>';
    }

    $marker = "data-cap-stats=\"{$page}-{$group}\"";
    return <<<HTML
<div {$marker}>
<style>
.cap-stats-grid { display:flex; justify-content:center; gap:0; max-width:980px; margin:0 auto; padding:20px 0; }
.cap-stat-item { flex:1; text-align:center; padding:0 24px; border-right:1px solid #ccc; display:flex; flex-direction:column; justify-content:center; }
.cap-stat-item:last-child { border-right:none; }
.cap-stat-number { font-family:'Open Sans',sans-serif; font-size:52px; font-weight:300; color:#96ce79; line-height:1.2; margin-bottom:8px; }
.cap-stat-text { font-family:'Open Sans',sans-serif; font-size:17px; color:#414141; line-height:1.5; }
@media (max-width:768px) {
    .cap-stats-grid { flex-direction:column; gap:20px; }
    .cap-stat-item { border-right:none; border-bottom:1px solid #ccc; padding:16px 0; }
    .cap-stat-item:last-child { border-bottom:none; }
    .cap-stat-number { font-size:36px; }
}
</style>
<div class="cap-stats-grid">{$items}</div>
</div><!-- /cap-stats -->
HTML;
}

function findClosingTag($html, $startPos, $tag = 'div') {
    $open = "<{$tag}";
    $close = "</{$tag}>";
    $openLen = strlen($open);
    $closeLen = strlen($close);
    $depth = 0;
    $len = strlen($html);
    $i = $startPos;
    while ($i < $len) {
        if (substr($html, $i, $openLen) === $open) {
            $depth++;
        } elseif (substr($html, $i, $closeLen) === $close) {
            $depth--;
            if ($depth === 0) {
                return $i + $closeLen;
            }
        }
        $i++;
    }
    return false;
}

function generatePostHtml($news, $gallery, $settings, $allNews = []) {
    $title = htmlspecialchars($news['title'], ENT_QUOTES, 'UTF-8');
    $body = $news['body'] ?? '';
    $date = $news['date'] ? formatDateSpanish($news['date']) : '';
    $mainImage = $news['image'] ?? '';
    $siteName = htmlspecialchars($settings['site_name'] ?? 'Corporación Abriendo Puertas', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($settings['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone1 = htmlspecialchars($settings['phone1'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone2 = htmlspecialchars($settings['phone2'] ?? '', ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($settings['address'] ?? '', ENT_QUOTES, 'UTF-8');
    $facebook = htmlspecialchars($settings['facebook'] ?? '', ENT_QUOTES, 'UTF-8');
    $instagram = htmlspecialchars($settings['instagram'] ?? '', ENT_QUOTES, 'UTF-8');
    $donateUrl = htmlspecialchars($settings['donate_url'] ?? '', ENT_QUOTES, 'UTF-8');
    $copyright = htmlspecialchars($settings['copyright'] ?? '', ENT_QUOTES, 'UTF-8');

    // Filter gallery: skip thumbnails (files under 15KB are Wix thumbnails)
    $filteredGallery = [];
    foreach ($gallery as $img) {
        $filePath = SITE_DIR . '/' . $img['filename'];
        if (file_exists($filePath) && filesize($filePath) > 15000) {
            $filteredGallery[] = $img;
        }
    }

    $galleryHtml = '';
    if (!empty($filteredGallery)) {
        $galleryHtml .= '<div class="post-gallery">';
        foreach ($filteredGallery as $img) {
            $src = htmlspecialchars($img['filename'], ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($img['alt_text'] ?? '', ENT_QUOTES, 'UTF-8');
            $galleryHtml .= '<figure class="post-gallery__item">'
                . '<img src="../' . $src . '" alt="' . $alt . '" loading="lazy">'
                . '</figure>';
        }
        $galleryHtml .= '</div>';
    }

    $mainImageHtml = '';
    if ($mainImage) {
        $mainImageHtml = '<div class="post-hero-img">'
            . '<img src="../' . htmlspecialchars($mainImage, ENT_QUOTES, 'UTF-8') . '" alt="' . $title . '">'
            . '</div>';
    }

    // Build "Entradas recientes" section - 3 most recent posts excluding current
    $recentHtml = '';
    if (!empty($allNews)) {
        $recent = [];
        foreach ($allNews as $r) {
            if ($r['id'] == $news['id']) continue;
            if (count($recent) >= 3) break;
            $recent[] = $r;
        }
        if (!empty($recent)) {
            $recentHtml = '<div class="recent-entries">'
                . '<div class="recent-entries__header">'
                . '<h2>Entradas recientes</h2>'
                . '<a href="../noticias.html">Ver todo</a>'
                . '</div>'
                . '<div class="recent-entries__list">';
            foreach ($recent as $r) {
                $rTitle = htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8');
                $rSlug = htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8');
                $rDate = $r['date'] ? formatDateSpanish($r['date']) : '';
                $rImg = htmlspecialchars($r['image'] ?? '', ENT_QUOTES, 'UTF-8');
                $recentHtml .= '<a href="' . $rSlug . '.html" class="recent-entry">'
                    . '<div class="recent-entry__img">'
                    . '<img src="../' . $rImg . '" alt="' . $rTitle . '" loading="lazy">'
                    . '</div>'
                    . '<div class="recent-entry__text">'
                    . '<div class="recent-entry__title">' . $rTitle . '</div>'
                    . '<div class="recent-entry__date">' . $rDate . '</div>'
                    . '</div>'
                    . '</a>';
            }
            $recentHtml .= '</div></div>';
        }
    }

    $donateSvg = '<svg viewBox="0 0 65.63 60.33" xmlns="http://www.w3.org/2000/svg" width="22" height="20" style="vertical-align:middle;margin-left:6px;"><g><path d="M31.95 6.81c1.18-1 2.27-1.93 3.38-2.83C40.7-.35 46.6-1.16 52.88 1.6A21 21 0 0 1 65.6 22.24a20.71 20.71 0 0 1-6.06 13.72 3.39 3.39 0 0 1-2.75 1.05 5.88 5.88 0 0 1-6.11 5.16 6 6 0 0 1-1.73 4.38 5.81 5.81 0 0 1-4.28 1.62c-.7 4.28-1.81 5.38-6.09 6.12a18.57 18.57 0 0 1-.3 1.84 5.64 5.64 0 0 1-9.37 2.57c-7.71-7.65-15.46-15.25-23-23a21.19 21.19 0 0 1 7-34.14 16.18 16.18 0 0 1 17.88 4c.37.38.74.79 1.16 1.25Zm-2.16 1.8c-.41-.46-.75-.84-1.09-1.2a13.26 13.26 0 0 0-14.41-3.45 18.41 18.41 0 0 0-6.3 30c5.43 5.57 11 11 16.5 16.52 2.08 2.09 4.16 4.19 6.26 6.25a2.87 2.87 0 0 0 4.08 0 2.92 2.92 0 0 0 .05-4.09c-.3-.33-.63-.65-1-1-1.93-1.92-3.86-3.84-5.77-5.77a1.35 1.35 0 0 1 .36-2.3 1.47 1.47 0 0 1 1.7.45c2.14 2.17 4.3 4.32 6.46 6.48a2.86 2.86 0 0 0 2.59.93 2.74 2.74 0 0 0 2.34-2 2.82 2.82 0 0 0-.74-3c-2.21-2.21-4.42-4.41-6.62-6.63a1.37 1.37 0 0 1-.12-2 1.42 1.42 0 0 1 2.08.08l1 1c1.87 1.87 3.72 3.77 5.62 5.6a2.91 2.91 0 1 0 4-4.2c-1.99-2.11-4.18-4.23-6.28-6.4a2 2 0 0 1-.57-1.37 1.72 1.72 0 0 1 .86-1.08 1.3 1.3 0 0 1 1.55.43c2.19 2.2 4.37 4.4 6.58 6.58a2.91 2.91 0 1 0 4.09-4.13q-5.6-6.27-11.22-12.51c-1.29-1.44-2.59-2.87-3.93-4.35l-.7.52c-3.28 2.51-6.54 5-9.85 7.51a5.7 5.7 0 0 1-7-9c1-.8 1.95-1.6 2.91-2.41Zm33.09 11.72a18.23 18.23 0 0 0-8.51-14.82c-6-3.95-12.68-3.57-17.76 1-1.57 1.41-3.24 2.73-4.86 4.08l-9.63 8a2.83 2.83 0 0 0-1.15 2.92 2.87 2.87 0 0 0 4.65 1.71c3.74-2.82 7.45-5.67 11.17-8.52 1-.79 1.59-.77 2.47.19q2.47 2.7 4.93 5.42l12.21 13.4c.4.44.73.74 1.28.17a18.7 18.7 0 0 0 5.2-13.55Z" fill="#ffffff"/></g></svg>';

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} — {$siteName}</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap');

        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Open Sans',sans-serif; color:#333; background:#fff; }
        a { color:#6EBE44; text-decoration:none; }
        a:hover { text-decoration:underline; }

        /* Header - matches original site */
        .site-header {
            background:#616161;
            padding:0;
            position:sticky; top:0; z-index:100;
            box-shadow:0 0 5px rgba(0,0,0,0.7);
        }
        .site-header__inner {
            display:flex; align-items:center; height:108px;
            padding:0 100px;
        }
        .site-header__logo { flex-shrink:0; }
        .site-header__logo img { height:100px; width:auto; }
        .site-header__nav { display:flex; align-items:center; gap:2px; flex:1; justify-content:center; }
        .site-header__nav a {
            font-family:'Open Sans',sans-serif;
            font-size:15px; font-weight:400; text-transform:uppercase;
            color:#fff; padding:10px 14px;
            transition:color .2s;
        }
        .site-header__nav a:hover { color:#6EBE44; text-decoration:none; }
        .site-header__nav a.active { color:#6EBE44; }
        .site-header__donate {
            display:inline-flex; align-items:center;
            background:#6EBE44;
            color:#fff !important; padding:10px 18px;
            border-radius:0; font-weight:400; font-size:15px;
            transition:background .2s; flex-shrink:0;
        }
        .site-header__donate:hover { background:#5aa636; text-decoration:none; }

        /* Post content */
        .post-container { max-width:780px; margin:0 auto; padding:50px 24px 60px; }
        .post-back { font-size:14px; color:#999; margin-bottom:28px; display:inline-block; }
        .post-back:hover { color:#6EBE44; text-decoration:none; }
        .post-title { font-size:34px; font-weight:700; line-height:1.3; margin-bottom:10px; color:#222; }
        .post-date { font-size:14px; color:#999; margin-bottom:28px; }
        .post-hero-img { margin-bottom:32px; border-radius:8px; overflow:hidden; }
        .post-hero-img img { width:100%; height:auto; display:block; }
        .post-body { font-size:16px; line-height:1.85; color:#444; }
        .post-body p { margin-bottom:18px; }
        .post-body img { max-width:100%; height:auto; border-radius:8px; margin:20px 0; }

        /* Gallery */
        .post-gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; margin:36px 0; }
        .post-gallery__item { border-radius:8px; overflow:hidden; }
        .post-gallery__item img { width:100%; height:auto; display:block; }

        /* Footer - matches original site */
        .site-footer {
            background:#616161; color:#fff;
            padding:36px 24px 24px; margin-top:60px;
        }
        .site-footer__inner {
            max-width:1100px; margin:0 auto;
            display:flex; justify-content:space-between; align-items:flex-start;
            flex-wrap:wrap; gap:30px;
        }
        .site-footer__newsletter { flex:1; min-width:250px; }
        .site-footer__newsletter p { color:#6EBE44; font-size:16px; margin-bottom:12px; }
        .site-footer__newsletter-form { display:flex; gap:0; }
        .site-footer__newsletter-form input {
            flex:1; padding:10px 14px; border:1px solid #888; border-right:none;
            background:transparent; color:#fff; font-size:14px;
            font-family:inherit; outline:none;
        }
        .site-footer__newsletter-form input::placeholder { color:#aaa; }
        .site-footer__newsletter-form button {
            padding:10px 20px; background:#6EBE44; color:#fff; border:none;
            font-family:inherit; font-size:14px; cursor:pointer; transition:background .2s;
        }
        .site-footer__newsletter-form button:hover { background:#5aa636; }
        .site-footer__contact { text-align:right; }
        .site-footer__contact p { font-size:14px; color:#ddd; line-height:1.8; }
        .site-footer__bottom {
            max-width:1100px; margin:20px auto 0;
            padding-top:16px; border-top:1px solid #888;
            text-align:center; font-size:13px; color:#ccc;
        }

        /* Recent entries */
        .recent-entries { max-width:780px; margin:0 auto; padding:0 24px 40px; }
        .recent-entries__header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #e0e0e0; }
        .recent-entries__header h2 { font-size:20px; font-weight:700; color:#333; margin:0; }
        .recent-entries__header a { font-size:14px; color:#6EBE44; }
        .recent-entries__list { display:flex; flex-direction:column; gap:20px; }
        .recent-entry { display:flex; gap:16px; text-decoration:none; color:inherit; }
        .recent-entry:hover { text-decoration:none; }
        .recent-entry__img { flex-shrink:0; width:180px; height:120px; border-radius:6px; overflow:hidden; }
        .recent-entry__img img { width:100%; height:100%; object-fit:cover; }
        .recent-entry__text { display:flex; flex-direction:column; justify-content:center; }
        .recent-entry__title { font-size:16px; font-weight:600; color:#333; line-height:1.4; margin-bottom:6px; }
        .recent-entry:hover .recent-entry__title { color:#6EBE44; }
        .recent-entry__date { font-size:13px; color:#999; }

        @media (max-width:768px) {
            .site-header__nav { display:none; }
            .site-header__inner { height:70px; }
            .site-header__logo img { height:60px; }
            .post-title { font-size:24px; }
            .post-gallery { grid-template-columns:1fr; }
            .site-footer__inner { flex-direction:column; }
            .site-footer__contact { text-align:left; }
            .recent-entry__img { width:120px; height:80px; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="site-header__inner">
        <a href="../index.html" class="site-header__logo">
            <img src="../_wix_assets/59b3c6c9e029fd11.png" alt="{$siteName}">
        </a>
        <nav class="site-header__nav">
            <a href="../index.html">Inicio</a>
            <a href="../nosotros.html">Nosotros</a>
            <a href="../quehacemos.html">Qué Hacemos</a>
            <a href="../noticias.html" class="active">Noticias</a>
            <a href="../hazteparte.html">Hazte Parte</a>
            <a href="../otec-promueve.html">OTEC - Promueve</a>
        </nav>
        <a href="{$donateUrl}" target="_blank" class="site-header__donate">DONAR AHORA{$donateSvg}</a>
    </div>
</header>

<article class="post-container">
    <a href="../noticias.html" class="post-back">&larr; Volver a noticias</a>
    <h1 class="post-title">{$title}</h1>
    <div class="post-date">{$date}</div>
    {$mainImageHtml}
    <div class="post-body">
        {$body}
    </div>
    {$galleryHtml}
</article>

{$recentHtml}

<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__newsletter">
            <p>Suscr&iacute;bete a nuestra newsletter</p>
            <div class="site-footer__newsletter-form">
                <input type="email" placeholder="Ingresa tu email aqu&iacute;*">
                <button type="button">Unirse</button>
            </div>
        </div>
        <div class="site-footer__contact">
            <p>{$email}</p>
            <p>{$phone1}&nbsp;&nbsp;{$phone2}</p>
            <p>{$address}</p>
        </div>
    </div>
    <div class="site-footer__bottom">{$copyright}</div>
</footer>

</body>
</html>
HTML;
}

function formatDateSpanish($dateStr) {
    $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;
    $d = (int)date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts);
    return "$d de $m de $y";
}
