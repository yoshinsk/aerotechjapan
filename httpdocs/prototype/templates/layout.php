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

/** 左サイドメニュー（旧 f-left.html の AERO PARTS カテゴリ相当） */
function nav_categories(): array
{
    return [
        ['label' => '乱人 / RANDO',        'href' => '#'],
        ['label' => '乱人 Black Edition',  'href' => '#'],
        ['label' => 'DIRect',              'href' => '#'],
        ['label' => 'AVANT',               'href' => '#'],
        ['label' => 'RANDO Style',         'href' => '#'],
        ['label' => 'RANDO SPORTS',        'href' => '#'],
        ['label' => '乱人流 SPORTS',       'href' => '#'],
        ['label' => 'Rando Ryu LUX',       'href' => '#'],
    ];
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

function layout_header(string $title): void
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
                <?php foreach (nav_categories() as $cat): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e($cat['href']) ?>"><?= e($cat['label']) ?></a>
                    </li>
                <?php endforeach; ?>
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
