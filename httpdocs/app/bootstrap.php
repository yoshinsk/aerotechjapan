<?php
/**
 * httpdocs/app/bootstrap.php
 * PHP CMSの起動処理、設定読込、セッション開始、共通クラス読込を行います。
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

define('APP_ROOT', __DIR__);
define('HTTPDOCS_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', HTTPDOCS_ROOT . '/public');

$config = require APP_ROOT . '/config.default.php';
$localConfig = HTTPDOCS_ROOT . '/config/config.local.php';
if (is_file($localConfig)) {
    $config = array_replace_recursive($config, require $localConfig);
}
$GLOBALS['aerotech_config'] = $config;

$sessionName = $config['security']['session_name'] ?? 'AEROTECHCMS';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($sessionName);
    $secureRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string)($_SERVER['SERVER_PORT'] ?? '') === '443'
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    session_set_cookie_params([
        'lifetime' => (int)($config['security']['session_lifetime'] ?? 0),
        'path' => '/',
        'secure' => $secureRequest || (bool)($config['security']['require_https'] ?? true),
        'httponly' => true,
        'samesite' => (string)($config['security']['session_same_site'] ?? 'Lax'),
    ]);
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
}

require_once APP_ROOT . '/functions.php';
require_once APP_ROOT . '/Database.php';
require_once APP_ROOT . '/Auth.php';
require_once APP_ROOT . '/CmsRepository.php';
require_once APP_ROOT . '/ImageService.php';
require_once APP_ROOT . '/FileUploadService.php';
require_once APP_ROOT . '/BusinessCalendar.php';
require_once APP_ROOT . '/Mailer.php';
require_once APP_ROOT . '/OpenAITranslator.php';
require_once APP_ROOT . '/render.php';

set_locale_from_request();
