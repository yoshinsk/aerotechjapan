<?php
/**
 * httpdocs/database/seed_site_directories.php
 * 販売代理店と車種別サイトマップの初期データを安全に追加します。
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$seedPath = __DIR__ . '/site_directory_seed.json';
$entries = json_decode((string)file_get_contents($seedPath), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($entries)) {
    throw new RuntimeException('初期データを読み込めませんでした。');
}

$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $exists = $pdo->prepare('SELECT id FROM site_directory_entries WHERE directory_type = ? AND group_name = ? AND title = ? AND detail = ? LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO site_directory_entries (directory_type, group_name, title, detail, product_slug, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $added = 0;
    foreach ($entries as $entry) {
        $type = (string)($entry['type'] ?? '');
        $group = (string)($entry['group'] ?? '');
        $title = (string)($entry['title'] ?? '');
        $detail = (string)($entry['detail'] ?? '');
        if (!in_array($type, ['distributor', 'sitemap'], true) || $title === '') {
            continue;
        }
        $exists->execute([$type, $group, $title, $detail]);
        if ($exists->fetchColumn() !== false) {
            continue;
        }
        $insert->execute([
            $type,
            $group,
            $title,
            $detail,
            (string)($entry['product_slug'] ?? ''),
            (int)($entry['sort_order'] ?? 100),
            (int)($entry['is_active'] ?? 1),
        ]);
        $added++;
    }
    $pdo->commit();
    echo "Added {$added} site directory entries.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
