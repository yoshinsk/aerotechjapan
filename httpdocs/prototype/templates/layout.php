<?php
/**
 * 共通レイアウト（ヘッダ／サイドメニュー／フッタ）。
 *
 * 旧サイトは frameset（f-top / f-left / f-main）で画面を分割していたが、
 * ここでは1枚のレスポンシブHTMLに統合する。
 * デザインの雰囲気（黒背景・白文字・シアンのリンク）は踏襲しつつ、
 * Bootstrap 5 でスマホ／タブレットに対応する。
 */

require_once __DIR__ . '/../config.php';

/** AERO PARTS の正式カテゴリ一覧（旧 f-left.html のメニュー定義に対応） */
function canonical_categories(): array
{
    return [
        '乱人 / RANDO',
        '乱人 Black Edition',
        'DIRect',
        'AVANT',
        'RANDO Style',
        'RANDO SPORTS',
        '乱人流 SPORTS',
        'Rando Ryu LUX',
    ];
}

/**
 * 指定カテゴリに属する商品を返す。
 * 商品の category は正式名を " / " で連結した文字列なので、
 * 正式名が含まれているかどうかで判定する（"未分類" は完全一致）。
 */
function products_in_category(array $all, string $label): array
{
    if ($label === '未分類') {
        return array_values(array_filter($all, fn($p) => ($p['category'] ?? '') === '未分類'));
    }
    return array_values(array_filter(
        $all,
        fn($p) => strpos((string) ($p['category'] ?? ''), $label) !== false
    ));
}

/** 商品カード（一覧で再利用） */
function render_product_card(array $p): void
{
    $thumb = '';
    if (!empty($p['images'])) {
        $thumb = SITE_BASE . rtrim($p['image_dir'], '/') . '/' . $p['images'][0];
    }
    ?>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card bg-dark border-secondary h-100">
            <?php if ($thumb): ?>
                <a href="product.php?slug=<?= urlencode($p['slug']) ?>">
                    <img src="<?= e($thumb) ?>" class="card-img-top" alt="<?= e($p['name']) ?>" loading="lazy">
                </a>
            <?php endif; ?>
            <div class="card-body">
                <h2 class="h6 card-title">
                    <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="stretched-link text-decoration-none">
                        <?= e($p['name']) ?>
                    </a>
                </h2>
                <div class="text-secondary small"><?= e($p['model_year']) ?></div>
            </div>
        </div>
    </div>
    <?php
}

/** 上部メニュー（旧 f-top.html の横並びメニュー相当） */
function nav_top(): array
{
    return [
        ['label' => 'TOP',      'href' => 'index.php'],
        ['label' => 'EVENT',    'href' => '#'],
        ['label' => 'MAGAZINE', 'href' => '#'],
        ['label' => 'ORDER',    'href' => '#'],
        ['label' => 'SITE MAP', 'href' => '#'],
        ['label' => 'LINK',     'href' => '#'],
        ['label' => 'ABOUT',    'href' => '#'],
    ];
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function layout_header(string $title, string $activeCat = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?>｜エアロテックジャパン</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/site.css" rel="stylesheet">
</head>
<body>
<!-- ヘッダ -->
<header class="border-bottom border-secondary">
    <nav class="navbar navbar-expand-lg navbar-dark px-3">
        <a class="navbar-brand fw-bold" href="index.php">
            <span class="brand-accent">AERO</span>TECH JAPAN
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach (nav_top() as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</header>

<div class="container-fluid">
    <div class="row">
        <!-- 左サイドメニュー（スマホでは上部に折りたたみ表示） -->
        <aside class="col-lg-2 sidebar py-3">
            <h2 class="sidebar-title">AERO PARTS</h2>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link<?= $activeCat === '*' ? ' active' : '' ?>" href="index.php">すべて</a>
                </li>
                <?php foreach (canonical_categories() as $cat): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $activeCat === $cat ? ' active' : '' ?>"
                           href="category.php?cat=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link<?= $activeCat === '未分類' ? ' active' : '' ?>"
                       href="category.php?cat=<?= urlencode('未分類') ?>">未分類</a>
                </li>
            </ul>
        </aside>

        <!-- 本文 -->
        <main class="col-lg-10 py-3">
    <?php
}

function layout_footer(): void
{
    ?>
        </main>
    </div>
</div>

<footer class="text-center py-4 border-top border-secondary">
    <small>&copy; AERO TECH JAPAN（試作版 / prototype）</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}
