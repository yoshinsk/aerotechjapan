<?php
/**
 * httpdocs/config/config.sample.php
 * 本番環境ごとのDB・メール・管理者設定を定義するサンプル設定です。
 */

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'aerotech_cms',
        'user' => 'aerotech_user',
        'pass' => 'CHANGE_ME',
    ],
    'mail' => [
        'to' => 'rando@aero-tech.co.jp',
        'from' => 'no-reply@aero-tech.co.jp',
    ],
    'openai' => [
        'model' => 'gpt-5.4-mini',
        'base_url' => 'https://api.openai.com/v1',
        'reasoning_effort' => 'low',
        'timeout' => 30,
    ],
];
