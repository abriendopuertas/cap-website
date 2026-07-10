<?php
require_once __DIR__ . '/db.php';

$d = db();
$backupDir = '/tmp/cap_backup_posts/www.corporacionabriendopuertas.cl/post';

if (!is_dir($backupDir)) {
    die("Backup directory not found: $backupDir\n");
}

$updated = 0;
$skipped = 0;

foreach (glob($backupDir . '/*.html') as $file) {
    $slug = urldecode(pathinfo($file, PATHINFO_FILENAME));
    $content = file_get_contents($file);

    // Find in database
    $stmt = $d->prepare("SELECT id, title FROM news WHERE slug = ?");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) {
        echo "SKIP (not in DB): $slug\n";
        $skipped++;
        continue;
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

    // Extract body - find paragraphs after the post title
    $body = '';
    $titlePos = strpos($content, 'data-hook="post-title"');
    $afterTitle = $titlePos !== false ? substr($content, $titlePos) : $content;

    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/si', $afterTitle, $pMatches)) {
        $paragraphs = [];
        foreach ($pMatches as $idx => $group) {
            if ($idx !== 1) continue;
            foreach ($group as $pContent) {
                $text = trim(strip_tags(html_entity_decode($pContent, ENT_QUOTES, 'UTF-8')));
                if (stripos($text, 'Suscríbete') !== false) break;
                if (stripos($text, 'capcoordinador') !== false) break;
                if (stripos($text, 'Gracias por tu mensaje') !== false) continue;
                if (stripos($text, 'Entradas recientes') !== false) break;
                if (stripos($text, '© 20') !== false) continue;
                if (mb_strlen($text) > 20) {
                    $clean = strip_tags($pContent, '<a><strong><em><b><i><br>');
                    $clean = preg_replace('/<span[^>]*class="[^"]*wixGuard[^"]*"[^>]*>.*?<\/span>/s', '', $clean);
                    $clean = preg_replace('/<span[^>]*>|<\/span>/s', '', $clean);
                    $clean = trim($clean);
                    if (mb_strlen($clean) > 20) {
                        $paragraphs[] = '<p>' . $clean . '</p>';
                    }
                }
            }
        }
        $body = implode("\n", $paragraphs);
    }

    // Extract excerpt from body if empty
    $plainBody = strip_tags(html_entity_decode($body, ENT_QUOTES, 'UTF-8'));
    $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', $plainBody)), 0, 250, 'UTF-8');

    // Also look for the Spanish date display text (e.g., "11 ago 2025")
    if (!$date) {
        if (preg_match('/class="time-ago"[^>]*>(\d{1,2}\s+[a-záéíóú]+\.?\s+\d{4})/iu', $content, $m)) {
            $dateText = $m[1];
            $shortMonths = ['ene'=>1,'feb'=>2,'mar'=>3,'abr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'ago'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dic'=>12];
            if (preg_match('/(\d{1,2})\s+([a-záéíóú]+)\.?\s+(\d{4})/iu', $dateText, $dm)) {
                $mon = $shortMonths[mb_strtolower(mb_substr($dm[2], 0, 3, 'UTF-8'), 'UTF-8')] ?? null;
                if ($mon) {
                    $date = sprintf('%04d-%02d-%02d', $dm[3], $mon, $dm[1]);
                }
            }
        }
    }

    // Update database
    $d->prepare("UPDATE news SET date = ?, body = ?, excerpt = ?, updated_at = datetime('now') WHERE id = ?")
      ->execute([$date, $body, $excerpt, $row['id']]);

    $bodyLen = strlen($body);
    echo "UPDATED: $slug (date: $date, body: {$bodyLen} chars)\n";
    $updated++;
}

echo "\n--- Done: $updated updated, $skipped skipped ---\n";
