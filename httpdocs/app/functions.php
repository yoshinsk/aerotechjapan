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

function public_page_url(array $page): string
{
    $slug = trim((string)($page['slug'] ?? ''));
    if ($slug === 'home') {
        return url('/');
    }
    return url('/page/' . rawurlencode($slug));
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

function image_variant_path(array $image, string $variant = 'large'): string
{
    $key = $variant . '_path';
    $path = trim((string)($image[$key] ?? ''));
    if ($path !== '') {
        return $path;
    }
    if ($variant === 'large') {
        $path = trim((string)($image['path'] ?? ''));
        if ($path !== '') {
            return $path;
        }
    }
    return trim((string)($image['path'] ?? ''));
}

function image_variant_url(array $image, string $variant = 'large'): string
{
    return media_url(image_variant_path($image, $variant));
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
        return sanitize_rich_html($trimmed);
    }
    return nl2br(e($trimmed));
}

function sanitize_rich_html(string $html): string
{
    $html = preg_replace('/<\s*b(?:\s[^>]*)?>/i', '<strong>', $html) ?? $html;
    $html = preg_replace('/<\s*\/\s*b\s*>/i', '</strong>', $html) ?? $html;
    $html = preg_replace('/<\s*i(?:\s[^>]*)?>/i', '<em>', $html) ?? $html;
    $html = preg_replace('/<\s*\/\s*i\s*>/i', '</em>', $html) ?? $html;
    $html = preg_replace('/<font\s+[^>]*color=["\']?([^"\'>\s]+)["\']?[^>]*>/i', '<span style="color: $1;">', $html) ?? $html;
    $html = str_ireplace('</font>', '</span>', $html);
    $allowed = '<h2><h3><p><br><strong><em><ul><ol><li><table><thead><tbody><tr><th><td><a><img><div><section><span>';
    if (!class_exists('DOMDocument')) {
        return sanitize_rich_html_fallback($html, $allowed);
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $options = 0;
    if (defined('LIBXML_HTML_NOIMPLIED')) {
        $options |= LIBXML_HTML_NOIMPLIED;
    }
    if (defined('LIBXML_HTML_NODEFDTD')) {
        $options |= LIBXML_HTML_NODEFDTD;
    }
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="rich-root">' . $html . '</div>', $options);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $document->getElementById('rich-root');
    if (!$root) {
        return strip_tags($html, $allowed);
    }
    sanitize_dom_children($root);

    $result = '';
    foreach ($root->childNodes as $child) {
        $result .= $document->saveHTML($child);
    }
    return trim($result);
}

function sanitize_rich_html_fallback(string $html, string $allowed): string
{
    $html = preg_replace('#<\s*(script|style|iframe|form|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? $html;
    $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
    $html = strip_tags($html, $allowed);
    $allowedTags = [
        'h2', 'h3', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a', 'img', 'div', 'section', 'span',
    ];

    return trim(preg_replace_callback('/<\s*(\/?)([a-z0-9]+)([^>]*)>/i', function (array $matches) use ($allowedTags): string {
        $closing = $matches[1] === '/';
        $tag = strtolower($matches[2]);
        if (!in_array($tag, $allowedTags, true)) {
            return '';
        }
        if ($closing) {
            return in_array($tag, ['br', 'img'], true) ? '' : '</' . $tag . '>';
        }
        if ($tag === 'br') {
            return '<br>';
        }

        $attrs = sanitize_rich_html_fallback_attrs($tag, $matches[3] ?? '');
        $htmlAttrs = '';
        foreach ($attrs as $name => $value) {
            $htmlAttrs .= ' ' . $name . '="' . e($value) . '"';
        }
        return '<' . $tag . $htmlAttrs . '>';
    }, $html) ?? $html);
}

function sanitize_rich_html_fallback_attrs(string $tag, string $attrsText): array
{
    $attrs = [];
    preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s"\'>`]+))/', $attrsText, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $name = strtolower($match[1]);
        $value = trim((string)($match[3] ?? $match[4] ?? $match[5] ?? ''));
        if ($name === 'style' && in_array($tag, ['span', 'p', 'div', 'section', 'th', 'td'], true)) {
            $style = sanitize_color_style($value);
            if ($style !== '') {
                $attrs['style'] = $style;
            }
        } elseif ($tag === 'a' && $name === 'href' && preg_match('#^(https?://|mailto:|tel:|/)#i', $value) === 1) {
            $attrs['href'] = $value;
        } elseif ($tag === 'a' && $name === 'target' && $value === '_blank') {
            $attrs['target'] = '_blank';
        } elseif ($tag === 'a' && $name === 'rel') {
            $attrs['rel'] = 'noopener noreferrer';
        } elseif ($tag === 'img' && $name === 'alt') {
            $attrs['alt'] = $value;
        } elseif ($tag === 'img' && $name === 'src' && preg_match('#^(https?://|/)#i', $value) === 1) {
            $attrs['src'] = $value;
        } elseif (in_array($tag, ['th', 'td'], true) && in_array($name, ['colspan', 'rowspan'], true) && preg_match('/^[1-9][0-9]?$/', $value) === 1) {
            $attrs[$name] = $value;
        }
    }
    if ($tag === 'a' && ($attrs['target'] ?? '') === '_blank') {
        $attrs['rel'] = 'noopener noreferrer';
    }
    return $attrs;
}

function sanitize_dom_children(DOMNode $node): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if (!$child instanceof DOMElement) {
            continue;
        }
        sanitize_dom_children($child);
        sanitize_dom_element($child);
    }
}

function sanitize_dom_element(DOMElement $element): void
{
    $allowedTags = [
        'h2', 'h3', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a', 'img', 'div', 'section', 'span',
    ];
    $tag = strtolower($element->tagName);
    if (in_array($tag, ['script', 'style', 'iframe', 'form', 'object', 'embed'], true)) {
        $element->parentNode?->removeChild($element);
        return;
    }
    if (!in_array($tag, $allowedTags, true)) {
        $parent = $element->parentNode;
        if (!$parent) {
            return;
        }
        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
        return;
    }

    foreach (iterator_to_array($element->attributes) as $attribute) {
        $name = strtolower($attribute->name);
        $value = trim($attribute->value);
        $keep = false;
        if ($name === 'style' && in_array($tag, ['span', 'p', 'div', 'section', 'th', 'td'], true)) {
            $style = sanitize_color_style($value);
            if ($style !== '') {
                $element->setAttribute('style', $style);
                $keep = true;
            }
        } elseif ($tag === 'a' && $name === 'href') {
            $keep = preg_match('#^(https?://|mailto:|tel:|/)#i', $value) === 1;
        } elseif ($tag === 'a' && $name === 'target') {
            $keep = $value === '_blank';
        } elseif ($tag === 'a' && $name === 'rel') {
            $element->setAttribute('rel', 'noopener noreferrer');
            $keep = true;
        } elseif ($tag === 'img' && in_array($name, ['src', 'alt'], true)) {
            $keep = $name === 'alt' || preg_match('#^(https?://|/)#i', $value) === 1;
        } elseif (in_array($tag, ['th', 'td'], true) && in_array($name, ['colspan', 'rowspan'], true)) {
            $keep = preg_match('/^[1-9][0-9]?$/', $value) === 1;
        }
        if (!$keep) {
            $element->removeAttribute($attribute->name);
        }
    }
    if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
        $element->setAttribute('rel', 'noopener noreferrer');
    }
}

function sanitize_color_style(string $style): string
{
    $safe = [];
    if (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $style, $matches)) {
        $color = trim($matches[1]);
        if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $color) || preg_match('/^rgba?\(\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}\s*,\s*[0-9]{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $color)) {
            $safe[] = 'color: ' . $color . ';';
        }
    }
    if (preg_match('/(?:^|;)\s*font-weight\s*:\s*(bold|[6-9]00)\s*(?:;|$)/i', $style)) {
        $safe[] = 'font-weight: 700;';
    }
    if (preg_match('/(?:^|;)\s*font-style\s*:\s*italic\s*(?:;|$)/i', $style)) {
        $safe[] = 'font-style: italic;';
    }
    return implode(' ', $safe);
}
