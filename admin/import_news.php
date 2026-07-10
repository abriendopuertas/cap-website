<?php
require_once __DIR__ . '/db.php';

$d = db();
$postDir = SITE_DIR . '/post';

// Header/footer images to skip (appear in many posts)
$skipImages = [
    '../_wix_assets/5fbeb027cafb729a.jpg',
    '../_wix_assets/59b3c6c9e029fd11.png',
    '../_wix_assets/24536d_83aebfcb2782417da486a7b645a197da~mv2.jpg',
    '../_wix_assets/92a9d9_9560b377731a4b8483ad4b2e1907c18d~mv2.png',
];

// Also find the news card images from noticias.html to use as main image
$cardImages = [];
$noticiasHtml = file_get_contents(SITE_DIR . '/noticias.html');
if (preg_match_all('/href="post\/([^"]+)\.html".*?<img[^>]+src="([^"]+)"/s', $noticiasHtml, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        $cardImages[urldecode($m[1])] = $m[2];
    }
}

// Clear existing data for reimport
$d->exec("DELETE FROM news_images");
$d->exec("DELETE FROM news");

$imported = 0;

foreach (glob($postDir . '/*.html') as $file) {
    $slug = pathinfo($file, PATHINFO_FILENAME);
    $content = file_get_contents($file);

    // Extract title
    $title = $slug;
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $content, $m)) {
        $raw = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        if (strlen($raw) > 3) $title = $raw;
    }

    // Extract date
    $date = null;
    if (preg_match('/<time[^>]*datetime="([^"]+)"/i', $content, $m)) {
        $date = date('Y-m-d', strtotime($m[1]));
    } elseif (preg_match('/(\d{1,2})\s+de\s+(ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[a-záéíóú]*\.?\s+de\s+(\d{4})/iu', $content, $m)) {
        $months = ['ene'=>1,'feb'=>2,'mar'=>3,'abr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'ago'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dic'=>12];
        $mon = $months[mb_strtolower(mb_substr($m[2], 0, 3, 'UTF-8'), 'UTF-8')] ?? 1;
        $date = sprintf('%04d-%02d-%02d', $m[3], $mon, $m[1]);
    }

    // Main image: prefer the card image from noticias.html
    $mainImage = $cardImages[$slug] ?? '';

    // Find all content images (excluding header/footer)
    $contentImages = [];
    if (preg_match_all('/<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"[^>]*/i', $content, $imgMatches, PREG_SET_ORDER)) {
        foreach ($imgMatches as $im) {
            $src = $im[1];
            $alt = html_entity_decode($im[2], ENT_QUOTES, 'UTF-8');
            if (in_array($src, $skipImages)) continue;
            // Normalize path: remove ../ prefix
            $normalized = preg_replace('/^\.\.\//', '', $src);
            $contentImages[] = ['src' => $normalized, 'alt' => $alt];
        }
    }

    // If no main image from card, use first content image
    if (!$mainImage && !empty($contentImages)) {
        $mainImage = $contentImages[0]['src'];
    }

    // Extract body paragraphs
    $body = '';
    if (preg_match_all('/<p[^>]*class="[^"]*font_8[^"]*"[^>]*>(.*?)<\/p>/si', $content, $pMatches)) {
        $paragraphs = [];
        foreach ($pMatches[0] as $p) {
            $text = trim(strip_tags(html_entity_decode($p, ENT_QUOTES, 'UTF-8')));
            if (stripos($text, 'Suscríbete') !== false) break;
            if (stripos($text, 'capcoordinador') !== false) break;
            if (stripos($text, 'Gracias por tu mensaje') !== false) continue;
            if (strlen($text) > 10) {
                $paragraphs[] = $p;
            }
        }
        $body = implode("\n", $paragraphs);
    }

    // Excerpt
    $plainBody = strip_tags(html_entity_decode($body, ENT_QUOTES, 'UTF-8'));
    $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', $plainBody)), 0, 200, 'UTF-8');

    // Insert news
    $d->prepare("INSERT INTO news (title, slug, date, image, excerpt, body, active) VALUES (?,?,?,?,?,?,1)")
      ->execute([$title, $slug, $date, $mainImage, $excerpt, $body]);
    $newsId = $d->lastInsertId();

    // Insert content images
    foreach ($contentImages as $order => $img) {
        $d->prepare("INSERT INTO news_images (news_id, filename, alt_text, sort_order) VALUES (?,?,?,?)")
          ->execute([$newsId, $img['src'], $img['alt'], $order]);
    }

    $imgCount = count($contentImages);
    echo "Imported: $slug (main: " . basename($mainImage) . ", +{$imgCount} imgs)\n";
    $imported++;
}

echo "\n--- Done: $imported posts imported ---\n";

// Summary
$totalImgs = $d->query("SELECT COUNT(*) FROM news_images")->fetchColumn();
echo "Total images in gallery: $totalImgs\n";
