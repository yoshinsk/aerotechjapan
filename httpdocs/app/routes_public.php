<?php
/**
 * httpdocs/app/routes_public.php
 * 公開サイト側のURLを解決し、必要なデータをビューへ渡します。
 */

declare(strict_types=1);

if ($path === '/favicon.ico') {
    header('Content-Type: image/svg+xml; charset=UTF-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="8" fill="#e12d2d"/><path fill="#fff" d="M13 44 28 16h8l15 28h-8l-3-7H24l-3 7h-8Zm14-14h10l-5-11-5 11Z"/></svg>';
    return;
}

if ($path === '/') {
    render('home', [
        'repo' => $repo,
        'home' => $repo->page('home'),
        'categories' => $repo->categories(),
        'featuredProducts' => $repo->products(['featured' => true], 8),
        'latestProducts' => $repo->products([], 8),
        'newsPosts' => $repo->news(5),
        'businessCalendarMonth' => (new BusinessCalendar($repo))->months(1)[0] ?? null,
        'businessStatusLabels' => BusinessCalendar::statusLabels(),
        'title' => config_value('app.name'),
    ]);
    return;
}

$legacyPageMap = [
    '/index.html' => '/',
    '/index.php' => '/',
    '/f-main.html' => '/',
    '/about.html' => '/page/about',
    '/privacy.html' => '/page/privacy',
    '/news-window.html' => '/news',
    '/Afmail-order.html' => '/contact',
    '/newAfmail-order.html' => '/contact',
    '/postmail.cgi' => '/contact',
    '/event/event.html' => '/page/events',
    '/magazine.html' => '/page/magazine',
    '/link.html' => '/page/links',
    '/distributor-oversea.html' => '/page/distributors',
];
if (isset($legacyPageMap[$path])) {
    redirect_to($legacyPageMap[$path]);
}

$legacyCategoryMap = [
    '/aero-parts/aero-parts-01.html' => '/category/rando',
    '/aero-parts/aero-parts-06.html' => '/category/rando-black-edition',
    '/aero-parts/aero-parts-08.html' => '/category/direct',
    '/aero-parts/aero-parts-07.html' => '/category/avant',
    '/aero-parts/aero-parts-02.html' => '/category/rando-style',
    '/aero-parts/aero-parts-03.html' => '/category/rando-sports',
    '/aero-parts/aero-parts-04.html' => '/category/rando-ryu-sports',
    '/aero-parts/aero-parts-05.html' => '/category/rando-ryu-lux',
];
if (isset($legacyCategoryMap[$path])) {
    redirect_to($legacyCategoryMap[$path]);
}

if (preg_match('#^/garage-file/([^/]+)\.html$#', $path, $matches)) {
    redirect_to('/products/' . $matches[1]);
}

if ($path === '/products') {
    render('products', [
        'repo' => $repo,
        'categories' => $repo->categories(),
        'products' => $repo->products(['keyword' => $_GET['q'] ?? '']),
        'keyword' => trim((string)($_GET['q'] ?? '')),
        'title' => t('製品一覧', 'Products'),
    ]);
    return;
}

if ($path === '/price-lists') {
    render('price_lists', [
        'priceLists' => $repo->priceLists(),
        'title' => t('価格表リスト', 'Price Lists'),
    ]);
    return;
}

if (preg_match('#^/category/([^/]+)$#', $path, $matches)) {
    $category = $repo->categoryBySlug($matches[1]);
    if (!$category) {
        http_response_code(404);
        render('error', ['message' => 'カテゴリが見つかりません。']);
        return;
    }
    render('products', [
        'repo' => $repo,
        'categories' => $repo->categories(),
        'category' => $category,
        'products' => $repo->products(['category_id' => (int)$category['id']]),
        'keyword' => '',
        'title' => localized($category, 'name'),
    ]);
    return;
}

if (preg_match('#^/products/([^/]+)$#', $path, $matches)) {
    $product = $repo->productBySlug($matches[1]);
    if (!$product) {
        http_response_code(404);
        render('error', ['message' => '商品が見つかりません。']);
        return;
    }
    render('product', [
        'repo' => $repo,
        'product' => $product,
        'images' => $repo->productImages((int)$product['id']),
        'specs' => $repo->productSpecs((int)$product['id']),
        'title' => localized($product, 'name'),
    ]);
    return;
}

if ($path === '/news') {
    render('news', [
        'posts' => $repo->news(),
        'title' => t('ニュース', 'News'),
    ]);
    return;
}

if (preg_match('#^/news/([^/]+)$#', $path, $matches)) {
    $post = $repo->newsBySlug($matches[1]);
    if (!$post) {
        http_response_code(404);
        render('error', ['message' => 'ニュースが見つかりません。']);
        return;
    }
    render('news_detail', ['post' => $post, 'title' => localized($post, 'title')]);
    return;
}

if ($path === '/contact') {
    $product = null;
    $productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
    if ($productId > 0) {
        $product = $repo->productById($productId);
    }

    if (is_post()) {
        verify_csrf();
        $honeypot = (string)($_POST[config_value('security.honeypot_field')] ?? '');
        $startedAt = (int)($_POST['form_started_at'] ?? 0);
        if ($honeypot !== '' || (time() - $startedAt) < (int)config_value('security.minimum_form_seconds', 3)) {
            render('contact_done', ['title' => t('送信完了', 'Sent')]);
            return;
        }

        $inquiry = [
            'product_id' => $product ? (int)$product['id'] : null,
            'locale' => current_locale(),
            'name' => trim((string)($_POST['name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'company' => trim((string)($_POST['company'] ?? '')),
            'country' => trim((string)($_POST['country'] ?? '')),
            'message' => trim((string)($_POST['message'] ?? '')),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        if ($inquiry['name'] === '' || !filter_var($inquiry['email'], FILTER_VALIDATE_EMAIL) || $inquiry['message'] === '') {
            render('contact', [
                'product' => $product,
                'input' => $inquiry,
                'error' => t('必須項目を確認してください。', 'Please check the required fields.'),
                'title' => t('お問い合わせ', 'Contact'),
            ]);
            return;
        }

        $repo->saveInquiry($inquiry);
        (new Mailer())->sendInquiry($inquiry, $product);
        render('contact_done', ['title' => t('送信完了', 'Sent')]);
        return;
    }

    render('contact', [
        'product' => $product,
        'input' => [],
        'error' => null,
        'title' => t('お問い合わせ', 'Contact'),
    ]);
    return;
}

if (preg_match('#^/page/([^/]+)$#', $path, $matches)) {
    $page = $repo->page($matches[1]);
    if (!$page) {
        http_response_code(404);
        render('error', ['message' => 'ページが見つかりません。']);
        return;
    }
    $params = ['page' => $page, 'title' => localized($page, 'title')];
    if ($matches[1] === 'about') {
        $params['businessCalendarMonths'] = (new BusinessCalendar($repo))->months(2);
        $params['businessStatusLabels'] = BusinessCalendar::statusLabels();
    }
    render('page', $params);
    return;
}

http_response_code(404);
render('error', ['message' => 'ページが見つかりません。']);
