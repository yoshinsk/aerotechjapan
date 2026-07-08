<?php
/**
 * httpdocs/app/functions.php
 * URL生成、HTMLエスケープ、多言語、CSRFなど全画面で使う共通関数を提供します。
 */

declare(strict_types=1);

function config_value(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['aerotech_config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $dir === '/' ? '' : $dir;
}

function request_path(): string
{
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uriPath, $base)) {
        $uriPath = substr($uriPath, strlen($base));
    }
    $path = '/' . trim($uriPath, '/');
    return $path === '/' ? '/' : rtrim($path, '/');
}

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function require_https_request(): void
{
    if (is_https_request() || !(bool)config_value('security.require_https', true)) {
        return;
    }

    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    if ($host === '') {
        $host = 'aero-tech.co.jp';
    }
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return base_path() . ($path === '/' ? '/' : $path);
}

function asset_url(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function media_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return asset_url('img/no-image.svg');
    }
    if (str_starts_with($path, 'uploads/')) {
        return url('/' . $path);
    }
    return url('/media.php?path=' . rawurlencode($path));
}

function redirect_to(string $path): never
{
    header('Location: ' . url($path), true, 302);
    exit;
}

function set_locale_from_request(): void
{
    $allowed = config_value('app.locales', ['ja', 'en']);
    $requested = $_GET['lang'] ?? null;
    if (is_string($requested) && in_array($requested, $allowed, true)) {
        $_SESSION['locale'] = $requested;
        return;
    }
    if (!isset($_SESSION['locale'])) {
        $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $_SESSION['locale'] = str_starts_with($accept, 'en') ? 'en' : config_value('app.locale_default', 'ja');
    }
}

function current_locale(): string
{
    return $_SESSION['locale'] ?? config_value('app.locale_default', 'ja');
}

function localized(array $row, string $field, string $fallback = ''): string
{
    $locale = current_locale();
    $localizedKey = $field . '_' . $locale;
    $defaultKey = $field . '_ja';
    $value = trim((string)($row[$localizedKey] ?? ''));
    if ($value !== '') {
        return $value;
    }
    return trim((string)($row[$defaultKey] ?? $row[$field] ?? $fallback));
}

function t(string $ja, string $en): string
{
    return current_locale() === 'en' ? $en : $ja;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_is_valid(string $token): bool
{
    return $token !== '' && hash_equals($_SESSION['_csrf'] ?? '', $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !csrf_is_valid($token)) {
        http_response_code(419);
        exit('CSRF token mismatch.');
    }
}

function slugify(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/[^\pL\pN]+/u', '-', $value) ?? '';
    $value = trim($value, '-');
    $value = strtolower($value);
    return $value !== '' ? $value : bin2hex(random_bytes(4));
}

function slugify_ascii(string $value): string
{
    $value = trim($value);
    if (class_exists('Transliterator')) {
        $transliterated = Transliterator::create('Any-Latin; Latin-ASCII; Lower()')?->transliterate($value);
        if (is_string($transliterated)) {
            $value = $transliterated;
        }
    } else {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($converted)) {
            $value = $converted;
        }
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    $value = preg_replace('/-{2,}/', '-', $value) ?? $value;
    return $value !== '' ? mb_substr($value, 0, 120) : '';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function excerpt(string $value, int $length = 140): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    return mb_strlen($plain) > $length ? mb_substr($plain, 0, $length) . '...' : $plain;
}

function is_image_path(?string $path): bool
{
    return (bool)preg_match('/\.(jpe?g|png|gif|webp)$/i', (string)$path);
}

function render_rich_text(string $value): string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }
    $trimmed = preg_replace_callback('/\{\{media:([^}]+)\}\}/', fn($matches) => e(media_url(trim($matches[1]))), $trimmed) ?? $trimmed;
    $trimmed = preg_replace_callback('/\{\{url:([^}]+)\}\}/', fn($matches) => e(url(trim($matches[1]))), $trimmed) ?? $trimmed;
    if ($trimmed !== strip_tags($trimmed)) {
        return strip_tags($trimmed, '<h2><h3><p><br><strong><em><ul><ol><li><table><thead><tbody><tr><th><td><a><img><div><section><span>');
    }
    return nl2br(e($trimmed));
}
