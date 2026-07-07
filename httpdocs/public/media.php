<?php
/**
 * httpdocs/public/media.php
 * public外にある旧サイト画像/PDFを安全に読み出して配信します。
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$rawPath = rawurldecode((string)($_GET['path'] ?? ''));
$relative = ltrim(str_replace('\\', '/', $rawPath), '/');

if ($relative === '' || str_contains($relative, '..')) {
    http_response_code(404);
    exit;
}

$allowedPrefixes = ['garage-img/', 'img/', 'order/', 'parts/', 'dash-boad-table/', 'side-table/', 'event/'];
$allowed = false;
foreach ($allowedPrefixes as $prefix) {
    if (str_starts_with($relative, $prefix)) {
        $allowed = true;
        break;
    }
}
if (!$allowed) {
    http_response_code(403);
    exit;
}

$root = rtrim((string)config_value('app.legacy_root'), '/\\');
$file = realpath($root . '/' . $relative);
$rootReal = realpath($root);
if (!$file || !$rootReal || !str_starts_with($file, $rootReal) || !is_file($file)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($file) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=604800');
header('Content-Length: ' . filesize($file));
readfile($file);
