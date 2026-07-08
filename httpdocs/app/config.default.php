<?php
/**
 * httpdocs/app/config.default.php
 * CMS全体の既定設定を定義し、環境別設定で上書き可能にします。
 */

return [
    'app' => [
        'name' => 'AERO TECH JAPAN',
        'locale_default' => 'ja',
        'locales' => ['ja', 'en'],
        'legacy_root' => dirname(__DIR__),
        'upload_root' => dirname(__DIR__) . '/public/uploads',
        'log_root' => dirname(__DIR__) . '/storage/logs',
    ],
    'db' => [
        'host' => getenv('AEROTECH_DB_HOST') ?: 'localhost',
        'name' => getenv('AEROTECH_DB_NAME') ?: 'aerotech_cms',
        'user' => getenv('AEROTECH_DB_USER') ?: 'aerotech_user',
        'pass' => getenv('AEROTECH_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'to' => getenv('AEROTECH_MAIL_TO') ?: 'rando@aero-tech.co.jp',
        'from' => getenv('AEROTECH_MAIL_FROM') ?: 'no-reply@aero-tech.co.jp',
    ],
    'openai' => [
        'api_key' => getenv('AEROTECH_OPENAI_API_KEY') ?: getenv('OPENAI_API_KEY') ?: '',
        'model' => getenv('AEROTECH_OPENAI_MODEL') ?: 'gpt-5.4-mini',
        'base_url' => getenv('AEROTECH_OPENAI_BASE_URL') ?: 'https://api.openai.com/v1',
        'reasoning_effort' => getenv('AEROTECH_OPENAI_REASONING_EFFORT') ?: 'low',
        'timeout' => (int)(getenv('AEROTECH_OPENAI_TIMEOUT') ?: 30),
    ],
    'security' => [
        'session_name' => 'AEROTECHCMS',
        'require_https' => true,
        'session_lifetime' => 0,
        'session_idle_timeout' => 1800,
        'session_same_site' => 'Lax',
        'honeypot_field' => 'company_website',
        'minimum_form_seconds' => 3,
    ],
];
