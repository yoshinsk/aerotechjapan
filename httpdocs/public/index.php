<?php
/**
 * httpdocs/public/index.php
 * 公開サイトと管理画面の全リクエストを受けるフロントコントローラです。
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$path = request_path();
require_https_request();

$repo = new CmsRepository(Database::pdo());
$auth = new Auth(Database::pdo());

try {
    if ($path === '/admin/login') {
        if (is_post()) {
            verify_csrf();
            if ($auth->attempt(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''))) {
                redirect_to('/admin');
            }
            admin_render('login', ['error' => 'メールアドレスまたはパスワードが違います。']);
            exit;
        }
        admin_render('login', ['error' => null]);
        exit;
    }

    if ($path === '/admin/logout') {
        $auth->logout();
        redirect_to('/admin/login');
    }

    if (str_starts_with($path, '/admin')) {
        $user = $auth->requireLogin();
        require APP_ROOT . '/routes_admin.php';
        exit;
    }

    require APP_ROOT . '/routes_public.php';
} catch (Throwable $e) {
    http_response_code(500);
    if (str_starts_with($path, '/admin')) {
        admin_render('error', ['message' => $e->getMessage()]);
    } else {
        render('error', ['message' => $e->getMessage()]);
    }
}
