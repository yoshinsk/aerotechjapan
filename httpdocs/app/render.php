<?php
/**
 * httpdocs/app/render.php
 * 公開画面と管理画面のテンプレート描画を共通化します。
 */

declare(strict_types=1);

function render(string $view, array $params = [], string $layout = 'site'): void
{
    $path = request_path();
    extract($params, EXTR_SKIP);
    ob_start();
    require APP_ROOT . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require APP_ROOT . '/views/layouts/' . $layout . '.php';
}

function admin_render(string $view, array $params = []): void
{
    render('admin/' . $view, $params, 'admin');
}
