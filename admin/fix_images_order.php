<?php
require_once __DIR__ . '/db.php';

$d = db();

// Parse the original noticias.html from backup to get the correct order and images
$backupFile = '/tmp/cap_backup_posts/www.corporacionabriendopuertas.cl/noticias.html';
if (!file_exists($backupFile)) {
    die("Backup noticias.html not found. Extract it first.\n");
}

$html = file_get_contents($backupFile);

// Find the noticias-all container
$start = strpos($html, '<div class="noticias-all"');
$container = substr($html, $start);

// Extract cards in order: image, slug, title
preg_match_all(
    '/<div class="extra-news-card">\s*<img[^>]+src="([^"]+)"[^>]*>.*?<a href="post\/([^"]+)\.html">([^<]+)<\/a>/s',
    $container,
    $matches,
    PREG_SET_ORDER
);

echo "Found " . count($matches) . " cards in original\n\n";

$updated = 0;
foreach ($matches as $pos => $m) {
    $origImage = $m[1];
    $slug = urldecode($m[2]);
    $origTitle = html_entity_decode($m[3], ENT_QUOTES, 'UTF-8');
    $sortOrder = $pos + 1;

    // Update the news record with the correct image and a sort_order
    $stmt = $d->prepare("UPDATE news SET image = ?, title = ?, updated_at = datetime('now') WHERE slug = ?");
    $stmt->execute([$origImage, $origTitle, $slug]);

    if ($stmt->rowCount() > 0) {
        echo "FIXED #{$sortOrder}: {$slug}\n";
        echo "  Image: {$origImage}\n";
        echo "  Title: {$origTitle}\n";
        $updated++;
    } else {
        echo "SKIP: {$slug} (not found in DB)\n";
    }
}

echo "\n--- Fixed $updated posts ---\n";

// Now add a sort_order column if it doesn't exist, or use the original position
// We'll store the original order as a position number in date field interpretation
// Actually, let's just set the dates to preserve the original order
// The original order is the canonical order, so we need dates that sort correctly

// Get the original order with their current dates
echo "\n=== Fixing sort order via dates ===\n";
$allNews = $d->query("SELECT id, slug, date FROM news")->fetchAll();
$newsById = [];
foreach ($allNews as $n) {
    $newsById[$n['slug']] = $n;
}

// Assign dates that preserve the original order
// Use the existing dates but add a time component to break ties
foreach ($matches as $pos => $m) {
    $slug = urldecode($m[2]);
    if (!isset($newsById[$slug])) continue;

    $existing = $newsById[$slug];
    $date = $existing['date'] ?: '2023-01-01';

    // Add a time component: earlier position = later time (to sort DESC correctly)
    // Posts with same date: position 1 gets 23:59, position 2 gets 23:58, etc.
    $timeOffset = 59 - ($pos % 60);
    $dateWithTime = $date . ' 23:' . str_pad($timeOffset, 2, '0', STR_PAD_LEFT) . ':00';

    $d->prepare("UPDATE news SET date = ? WHERE slug = ?")
      ->execute([$dateWithTime, $slug]);
}

echo "Sort order fixed via date+time values.\n";
echo "Done.\n";
