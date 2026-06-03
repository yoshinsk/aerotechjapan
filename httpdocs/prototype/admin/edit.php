<?php
/**
 * 簡易商品編集フォーム（試作版）。
 * 「PHPによるページ単位の編集」を体験するための最小実装。
 *
 * 商品名・年式・カテゴリ・補足・SPEC（複数行）を編集して保存できる。
 * 保存先は config.php の USE_DB によって MariaDB か JSON に切り替わる。
 *
 * ※ 認証はパスワード1個のみの簡易実装。本番ではセッション認証＋
 *   ハッシュ化＋HTTPS必須に置き換えること。
 */

require_once __DIR__ . '/../lib/repository.php';

// レイアウトは prototype/ 直下を基準にしているので、admin/ から相対参照する
require_once __DIR__ . '/../templates/layout.php';

$repo = new ProductRepository();
$slug = $_GET['slug'] ?? ($_POST['slug'] ?? '');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') !== ADMIN_PASSWORD) {
        $error = 'パスワードが違います。';
    } else {
        $existing = $repo->find($slug);
        if (!$existing) {
            $error = '対象の商品が見つかりません。';
        } else {
            // SPEC は「ラベル|値」を1行ずつ受け取る
            $specs = [];
            foreach (preg_split('/\r\n|\r|\n/', $_POST['specs'] ?? '') as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = explode('|', $line, 2);
                $specs[] = [
                    'label' => trim($parts[0]),
                    'value' => isset($parts[1]) ? trim($parts[1]) : '',
                ];
            }

            $existing['name']       = trim($_POST['name'] ?? '');
            $existing['model_year'] = trim($_POST['model_year'] ?? '');
            $existing['category']   = trim($_POST['category'] ?? '');
            $existing['notes']      = trim($_POST['notes'] ?? '');
            $existing['specs']      = $specs;

            $repo->save($existing);
            $message = '保存しました。';
        }
    }
}

$product = $slug !== '' ? $repo->find($slug) : null;

// SPEC を「ラベル|値」のテキストへ戻す
$specsText = '';
if ($product && !empty($product['specs'])) {
    $lines = array_map(
        fn($s) => ($s['label'] ?? '') . '|' . ($s['value'] ?? ''),
        $product['specs']
    );
    $specsText = implode("\n", $lines);
}

// admin/ 配下なので CSS / リンクの基準を1つ上げる小細工は不要：
// layout は assets/site.css を相対指定しているため、ここでは簡易ヘッダを使う。
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>商品編集｜エアロテックジャパン（試作版）</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/site.css" rel="stylesheet">
</head>
<body>
<div class="container py-4" style="max-width: 760px;">
    <h1 class="h4 mb-3">商品編集 <span class="text-secondary small">（試作版）</span></h1>

    <?php if ($message): ?>
        <div class="alert alert-success py-2"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$product): ?>
        <p>商品が見つかりません。<a href="../index.php">一覧へ戻る</a></p>
    <?php else: ?>
        <form method="post" action="edit.php">
            <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">

            <div class="mb-3">
                <label class="form-label">商品名</label>
                <input type="text" name="name" class="form-control"
                       value="<?= e($product['name']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">適合年式</label>
                <input type="text" name="model_year" class="form-control"
                       value="<?= e($product['model_year']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">カテゴリ</label>
                <input type="text" name="category" class="form-control"
                       value="<?= e($product['category']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">補足説明</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($product['notes'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">SPEC（1行に「ラベル|値」）</label>
                <textarea name="specs" class="form-control" rows="6"><?= e($specsText) ?></textarea>
                <div class="form-text text-secondary">例：FOOTWORK|【RS★R】Best★i 車高調</div>
            </div>

            <div class="mb-3">
                <label class="form-label">編集パスワード</label>
                <input type="password" name="password" class="form-control" autocomplete="off">
            </div>

            <button type="submit" class="btn btn-info">保存する</button>
            <a class="btn btn-outline-secondary" href="../product.php?slug=<?= urlencode($product['slug']) ?>">表示を確認</a>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
